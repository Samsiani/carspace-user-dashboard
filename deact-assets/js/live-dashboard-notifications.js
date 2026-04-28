/**
 * Live Dashboard Notifications
 * 
 * Handles real-time notification updates with improved performance
 *
 * @package Carspace_Dashboard
 * @since 3.3
 * @version 3.4
 * 
 * Last updated: 2025-04-19 23:52:55 by Samsiani
 */
(function($) {
    "use strict";
    
    // Configuration
    const CONFIG = {
        initialDelay: 2000,            // Wait 2 seconds before first check
        activeInterval: 60000,         // 60 seconds when tab is active
        inactiveInterval: 300000,      // 5 minutes when tab is inactive
        maxFailedAttempts: 5,          // Max consecutive failures before switching endpoints
        maxDailyRequests: 1000,        // Maximum requests per day
        backoffMultiplier: 1.5,        // Exponential backoff multiplier
        maxBackoffDelay: 300000,       // Maximum backoff delay (5 minutes)
        minVisibleDuration: 60000      // User must be active for at least 1 minute to play sound
    };
    
    // State tracking
    let state = {
        failedAttempts: 0,
        useTestEndpoint: false,
        currentInterval: CONFIG.activeInterval,
        requestCount: 0,
        lastRequestTime: 0,
        backoffDelay: 0,
        intervalId: null,
        tabActive: true,
        userInteractedAt: 0,
        lastNotificationCount: 0
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        // Create badge immediately with 0 count
        updateNotificationBadge(0);
        
        // Set up visibility change listener
        setupVisibilityListener();
        
        // Track user interaction
        setupUserInteractionTracking();
        
        // Start notification system with initial delay
        setTimeout(initNotificationSystem, CONFIG.initialDelay);
    });
    
    /**
     * Initialize the notification system with proper timing controls
     */
    function initNotificationSystem() {
        // Check for notifications immediately
        checkForNewNotifications();
        
        // Set up dynamic interval based on tab visibility
        resetNotificationInterval();
    }
    
    /**
     * Set up page visibility change detection
     */
    function setupVisibilityListener() {
        // Update visibility state when tab focus changes
        document.addEventListener('visibilitychange', function() {
            state.tabActive = !document.hidden;
            
            // Adjust polling interval based on visibility
            resetNotificationInterval();
            
            if (state.tabActive) {
                // Tab became active, check notifications soon
                setTimeout(checkForNewNotifications, 1000);
            }
        });
    }
    
    /**
     * Track user interactions
     */
    function setupUserInteractionTracking() {
        // User interaction events
        const interactionEvents = ['click', 'touchstart', 'keydown', 'mousemove'];
        
        // Add listeners for each interaction type
        interactionEvents.forEach(function(eventType) {
            document.addEventListener(eventType, function() {
                state.userInteractedAt = Date.now();
                document.documentElement.setAttribute("data-user-interacted", "true");
            }, { passive: true });
        });
        
        // Initial state
        state.userInteractedAt = Date.now();
    }
    
    /**
     * Reset notification checking interval based on current state
     */
    function resetNotificationInterval() {
        // Clear existing interval
        if (state.intervalId) {
            clearInterval(state.intervalId);
        }
        
        // Calculate appropriate interval
        let interval = state.tabActive ? CONFIG.activeInterval : CONFIG.inactiveInterval;
        
        // Apply backoff if needed
        if (state.backoffDelay > 0) {
            interval = Math.min(state.backoffDelay, CONFIG.maxBackoffDelay);
        }
        
        state.currentInterval = interval;
        
        // Set new interval
        state.intervalId = setInterval(checkForNewNotifications, interval);
    }
    
    /**
     * Create or update notification badge with specific count
     */
    function updateNotificationBadge(count) {
        // Remove any existing badge first
        $("#dashboard-notif-count").remove();
        
        // Create the badge with appropriate styling
        const $bellIcon = $("a[title='Notifications'] .lucide-bell").closest("a");
        if ($bellIcon.length) {
            // Use different background color based on count
            const badgeClass = count > 0 ? "bg-danger" : "bg-secondary";
            
            $bellIcon.append("<span id='dashboard-notif-count' class='position-absolute top-0 start-100 translate-middle badge rounded-pill " + badgeClass + "' style='font-size: 0.7rem;'>" + count + "</span>");
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Check for new notifications via AJAX
     * Now with rate limiting and circuit breaker
     */
    function checkForNewNotifications() {
        // Don't exceed daily request limit
        if (state.requestCount >= CONFIG.maxDailyRequests) {
            return;
        }
        
        // Rate limiting - ensure at least 5 seconds between requests
        const now = Date.now();
        const timeSinceLastRequest = now - state.lastRequestTime;
        if (state.lastRequestTime > 0 && timeSinceLastRequest < 5000) {
            return;
        }
        
        // Update request tracking
        state.requestCount++;
        state.lastRequestTime = now;
        
        // Use either variable name to ensure compatibility
        const ajaxData = typeof carspace_ajax !== "undefined" ? carspace_ajax : 
                      (typeof carspace_notification_data !== "undefined" ? carspace_notification_data : null);
        
        if (!ajaxData) {
            return;
        }
        
        // Determine which action to use based on previous failures
        const action = state.useTestEndpoint ? "check_for_notifications_test" : "check_for_notifications";
        
        $.ajax({
            url: ajaxData.ajax_url,
            type: "POST",
            data: {
                action: action,
                nonce: ajaxData.nonce
            },
            dataType: 'json',
            timeout: 10000, // 10 second timeout
            success: function(response) {
                // Handle successful response
                if (response && response.success) {
                    handleSuccessfulRequest(response);
                } else {
                    handleFailedRequest();
                }
            },
            error: function(xhr, status, error) {
                handleFailedRequest();
            }
        });
    }
    
    /**
     * Handle successful AJAX request
     */
    function handleSuccessfulRequest(response) {
        // Reset failure counter and backoff
        state.failedAttempts = 0;
        state.backoffDelay = 0;
        
        // Process notification data
        processNotificationUpdate(response.data.count);
        
        // If we were using test endpoint but main endpoint works, switch back
        if (state.useTestEndpoint) {
            state.useTestEndpoint = false;
        }
        
        // Reset to normal interval
        if (state.currentInterval !== (state.tabActive ? CONFIG.activeInterval : CONFIG.inactiveInterval)) {
            resetNotificationInterval();
        }
    }
    
    /**
     * Handle failed AJAX request with exponential backoff
     */
    function handleFailedRequest() {
        state.failedAttempts++;
        
        // After certain failures, switch to test endpoint
        if (state.failedAttempts >= CONFIG.maxFailedAttempts && !state.useTestEndpoint) {
            state.useTestEndpoint = true;
        }
        
        // Implement exponential backoff
        if (state.failedAttempts > 1) {
            if (state.backoffDelay === 0) {
                state.backoffDelay = state.currentInterval;
            }
            state.backoffDelay = Math.min(state.backoffDelay * CONFIG.backoffMultiplier, CONFIG.maxBackoffDelay);
            
            // Reset interval with new backoff delay
            resetNotificationInterval();
        }
        
        // After multiple failures, make sure the badge is still visible
        if (state.failedAttempts >= CONFIG.maxFailedAttempts) {
            if ($("#dashboard-notif-count").length === 0) {
                updateNotificationBadge(state.lastNotificationCount);
            }
        }
    }
    
    /**
     * Process notification update - handles animation and sound
     */
    function processNotificationUpdate(newCount) {
        // Store current count for comparison
        const currentCount = state.lastNotificationCount;
        
        // Update stored count
        state.lastNotificationCount = newCount;
        
        // Debug log
        
        // Always update the badge with current count
        updateNotificationBadge(newCount);
        
        // If count increased, play sound and animate
        if (newCount > currentCount) {
            // Add pulse animation to new badge
            $("#dashboard-notif-count").addClass("pulse");
            setTimeout(function() {
                $("#dashboard-notif-count").removeClass("pulse");
            }, 1000);
            
            // Play notification sound if conditions are met:
            // 1. User has interacted with page
            // 2. Page is currently visible
            // 3. User has been active recently (within the last minute)
            const userActiveRecently = (Date.now() - state.userInteractedAt) < CONFIG.minVisibleDuration;
            
            if (document.documentElement.hasAttribute("data-user-interacted") && 
                state.tabActive && userActiveRecently) {
                playNotificationSound();
            }
        }
    }
    
    /**
     * Play notification sound
     */
    function playNotificationSound() {
        // Use either variable name to ensure compatibility
        const ajaxData = typeof carspace_ajax !== "undefined" ? carspace_ajax : 
                      (typeof carspace_notification_data !== "undefined" ? carspace_notification_data : null);
        
        if (!ajaxData || !ajaxData.plugin_url) {
            return;
        }
        
        const soundUrl = ajaxData.plugin_url + "assets/sound/notify.mp3";
        
        const audio = new Audio(soundUrl);
        
        // Try to play the sound
        audio.play().catch(function(e) {
        });
    }
    
})(jQuery);