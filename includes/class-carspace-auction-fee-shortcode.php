<?php
/**
 * Auction Fee Calculator — Standalone Shortcode
 *
 * Renders a self-contained calculator widget that can be embedded
 * on any WordPress page via [carspace_auction_fee].
 *
 * @package Carspace_Dashboard
 */

defined( 'ABSPATH' ) || exit;

class Carspace_Auction_Fee_Shortcode {

    private static $enqueued = false;

    public static function init() {
        add_shortcode( 'carspace_auction_fee', array( __CLASS__, 'render' ) );
    }

    /**
     * Render the calculator widget.
     */
    public static function render( $atts ) {
        if ( ! is_user_logged_in() ) {
            return '<p style="text-align:center;color:#666;">Please log in to use the Auction Fee Calculator.</p>';
        }

        $id = 'caf-' . wp_unique_id();

        ob_start();

        if ( ! self::$enqueued ) {
            self::$enqueued = true;
            self::render_styles();
        }

        ?>
        <div id="<?php echo esc_attr( $id ); ?>" class="caf-widget">
            <div class="caf-header">
                <svg class="caf-header-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="16" y1="14" x2="16" y2="14"/><line x1="16" y1="18" x2="16" y2="18"/><line x1="12" y1="14" x2="12" y2="14"/><line x1="12" y1="18" x2="12" y2="18"/><line x1="8" y1="14" x2="8" y2="14"/><line x1="8" y1="18" x2="8" y2="18"/><line x1="8" y1="10" x2="16" y2="10"/></svg>
                <span>Auction Fee Calculator</span>
            </div>

            <div class="caf-body">
                <!-- Auction selector -->
                <div class="caf-field">
                    <label class="caf-label">Choose Auction</label>
                    <div class="caf-auction-btns">
                        <button type="button" class="caf-auction-btn caf-active" data-auction="copart">Copart</button>
                        <button type="button" class="caf-auction-btn" data-auction="iaai">IAAI</button>
                    </div>
                </div>

                <!-- Bid price -->
                <div class="caf-field">
                    <label class="caf-label" for="<?php echo esc_attr( $id ); ?>-bid">Bid Price ($)</label>
                    <input type="number" id="<?php echo esc_attr( $id ); ?>-bid" class="caf-input" value="5000" min="0" step="100" placeholder="Enter bid price" />
                </div>

                <!-- Breakdown -->
                <div class="caf-result" style="display:none;">
                    <div class="caf-row">
                        <span class="caf-row-label">Bid Price</span>
                        <span class="caf-row-value" data-field="bid_price"></span>
                    </div>
                    <div class="caf-divider"></div>
                    <div class="caf-row">
                        <span class="caf-row-label">Non-Clean Title Fee</span>
                        <span class="caf-row-value" data-field="non_clean_title_fee"></span>
                    </div>
                    <div class="caf-row">
                        <span class="caf-row-label">Virtual Bid Fee</span>
                        <span class="caf-row-value" data-field="virtual_bid_fee"></span>
                    </div>
                    <div class="caf-divider"></div>
                    <div class="caf-row">
                        <span class="caf-row-label">Environmental Fee</span>
                        <span class="caf-row-value" data-field="environmental_fee"></span>
                    </div>
                    <div class="caf-row">
                        <span class="caf-row-label">Gate Fee</span>
                        <span class="caf-row-value" data-field="gate_fee"></span>
                    </div>
                    <div class="caf-row">
                        <span class="caf-row-label">Title Pickup Fee</span>
                        <span class="caf-row-value" data-field="title_pickup_fee"></span>
                    </div>
                    <div class="caf-divider"></div>
                    <div class="caf-row caf-row-charges">
                        <span class="caf-row-label">Total Charges</span>
                        <span class="caf-row-value caf-charges" data-field="charges"></span>
                    </div>
                    <div class="caf-divider"></div>
                    <div class="caf-row caf-row-total">
                        <span class="caf-row-label">Total Purchase Price</span>
                        <span class="caf-row-value caf-total" data-field="total"></span>
                    </div>
                </div>

                <!-- Loading -->
                <div class="caf-loading" style="display:none;">
                    <div class="caf-spinner"></div>
                    <span>Calculating...</span>
                </div>

                <!-- Empty state -->
                <div class="caf-empty">
                    Enter a bid price to see the breakdown
                </div>
            </div>
        </div>

        <script>
        (function() {
            var root = document.getElementById('<?php echo esc_js( $id ); ?>');
            var auction = 'copart';
            var bidInput = root.querySelector('.caf-input');
            var resultEl = root.querySelector('.caf-result');
            var loadingEl = root.querySelector('.caf-loading');
            var emptyEl = root.querySelector('.caf-empty');
            var auctionBtns = root.querySelectorAll('.caf-auction-btn');
            var timer = null;
            var restUrl = '<?php echo esc_url_raw( rest_url( 'carspace/v1/auction-fees/calculate' ) ); ?>';
            var nonce = '<?php echo wp_create_nonce( 'wp_rest' ); ?>';

            function fmt(v) {
                return '$' + Number(v).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
            }

            function calculate() {
                var bid = parseFloat(bidInput.value) || 0;
                if (bid <= 0) {
                    resultEl.style.display = 'none';
                    loadingEl.style.display = 'none';
                    emptyEl.style.display = '';
                    return;
                }

                emptyEl.style.display = 'none';
                loadingEl.style.display = '';

                fetch(restUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
                    credentials: 'same-origin',
                    body: JSON.stringify({ auction: auction, bid_price: bid })
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    loadingEl.style.display = 'none';
                    if (data && typeof data.total !== 'undefined') {
                        var fields = root.querySelectorAll('[data-field]');
                        for (var i = 0; i < fields.length; i++) {
                            var key = fields[i].getAttribute('data-field');
                            if (typeof data[key] !== 'undefined') {
                                fields[i].textContent = fmt(data[key]);
                            }
                        }
                        resultEl.style.display = '';
                    } else {
                        emptyEl.style.display = '';
                    }
                })
                .catch(function() {
                    loadingEl.style.display = 'none';
                    emptyEl.style.display = '';
                });
            }

            function debounceCalc() {
                clearTimeout(timer);
                timer = setTimeout(calculate, 300);
            }

            // Auction buttons
            for (var i = 0; i < auctionBtns.length; i++) {
                auctionBtns[i].addEventListener('click', function() {
                    for (var j = 0; j < auctionBtns.length; j++) {
                        auctionBtns[j].classList.remove('caf-active');
                    }
                    this.classList.add('caf-active');
                    auction = this.getAttribute('data-auction');
                    debounceCalc();
                });
            }

            bidInput.addEventListener('input', debounceCalc);

            // Initial calculation
            debounceCalc();
        })();
        </script>
        <?php

        return ob_get_clean();
    }

    /**
     * Output scoped CSS (once per page).
     */
    private static function render_styles() {
        ?>
        <style>
        .caf-widget {
            --caf-primary: #2563eb;
            --caf-primary-light: #eff6ff;
            --caf-border: #e5e7eb;
            --caf-bg: #ffffff;
            --caf-text: #1f2937;
            --caf-muted: #6b7280;
            --caf-charges: #d97706;
            --caf-radius: 12px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            max-width: 480px;
            margin: 0 auto;
            border: 1px solid var(--caf-border);
            border-radius: var(--caf-radius);
            background: var(--caf-bg);
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
        }
        .caf-widget * { box-sizing: border-box; margin: 0; padding: 0; }

        .caf-header {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 16px 20px;
            border-bottom: 1px solid var(--caf-border);
            font-size: 16px;
            font-weight: 600;
            color: var(--caf-text);
        }
        .caf-header-icon { width: 20px; height: 20px; color: var(--caf-primary); flex-shrink: 0; }

        .caf-body { padding: 20px; display: flex; flex-direction: column; gap: 16px; }

        .caf-field { display: flex; flex-direction: column; gap: 6px; }
        .caf-label { font-size: 13px; font-weight: 500; color: var(--caf-muted); }

        .caf-auction-btns { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .caf-auction-btn {
            padding: 10px 16px;
            border: 2px solid var(--caf-border);
            border-radius: 8px;
            background: var(--caf-bg);
            font-size: 14px;
            font-weight: 600;
            color: var(--caf-muted);
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .caf-auction-btn:hover { border-color: var(--caf-primary); color: var(--caf-text); }
        .caf-auction-btn.caf-active {
            border-color: var(--caf-primary);
            background: var(--caf-primary-light);
            color: var(--caf-primary);
        }

        .caf-input {
            height: 40px;
            width: 100%;
            padding: 0 12px;
            border: 1px solid var(--caf-border);
            border-radius: 8px;
            font-size: 14px;
            color: var(--caf-text);
            background: var(--caf-bg);
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .caf-input:focus {
            border-color: var(--caf-primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        .caf-input::placeholder { color: #9ca3af; }

        .caf-result {
            border: 1px solid var(--caf-border);
            border-radius: 10px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .caf-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
        }
        .caf-row-label { color: var(--caf-muted); }
        .caf-row-value { font-weight: 500; color: var(--caf-text); }

        .caf-divider {
            height: 1px;
            background: var(--caf-border);
            margin: 2px 0;
        }

        .caf-row-charges .caf-row-label { font-weight: 500; color: var(--caf-muted); }
        .caf-charges { color: var(--caf-charges) !important; font-weight: 600 !important; }

        .caf-row-total {
            font-size: 18px;
            font-weight: 700;
            padding-top: 4px;
        }
        .caf-row-total .caf-row-label { color: var(--caf-text); font-weight: 700; }
        .caf-total { color: var(--caf-primary) !important; font-weight: 700 !important; }

        .caf-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 32px;
            color: var(--caf-muted);
            font-size: 14px;
        }
        .caf-spinner {
            width: 18px; height: 18px;
            border: 2px solid var(--caf-border);
            border-top-color: var(--caf-primary);
            border-radius: 50%;
            animation: caf-spin 0.6s linear infinite;
        }
        @keyframes caf-spin { to { transform: rotate(360deg); } }

        .caf-empty {
            text-align: center;
            padding: 24px;
            font-size: 14px;
            color: var(--caf-muted);
            border: 1px dashed var(--caf-border);
            border-radius: 10px;
        }

        @media (max-width: 500px) {
            .caf-widget { border-radius: 8px; }
            .caf-body { padding: 16px; gap: 12px; }
            .caf-row-total { font-size: 16px; }
        }
        </style>
        <?php
    }
}
