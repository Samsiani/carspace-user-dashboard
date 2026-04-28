import{j as e,L as z,r as m,m as q,d as W}from"./react-vendor-C3ryBqjY.js";import{e as F,B as J,E as Q,j as Y,F as v,l as j}from"./main-k2vhot0h.js";import{I as Z,u as y}from"./InvoiceStatusBadge-BM-YXSUV.js";import{P as K}from"./PageHeader-INXKVrZm.js";import{C as o,b as d,c as p,a as x}from"./card-D0AGU63Z.js";import{T as X,a as ee,b as w,c as g,d as se,e as h,f as te}from"./table-RNzsKnz1.js";import{F as ae,a1 as ie,l as re,C as ne,R as le,Z as ce,a2 as oe,a3 as de,N as me,a4 as pe}from"./icons-DEA80qAW.js";import{f as B}from"./format-Dh0QxBet.js";import{I as xe}from"./InvoiceForm-EAs_9e27.js";import{C as he}from"./ConfirmDialog-DOJsmCkw.js";import{D as ue,a as fe,b as je,c as ge,d as be}from"./dialog-CWgZJJBr.js";import"./zod-BjsLooCQ.js";import"./index.esm-BmY8NfSR.js";import"./textarea-Dm0-YQBM.js";import"./select-CDsOjdcl.js";function n(s){return new Intl.NumberFormat("en-US",{style:"currency",currency:"USD",minimumFractionDigits:0,maximumFractionDigits:0}).format(s)}function _(s){return s?B(new Date(s),"MMM d, yyyy"):"-"}const Ne={standard:"Standard",proforma:"Proforma",credit_note:"Credit Note"};function ve({invoice:s}){const t=F();return e.jsxs("div",{className:"space-y-6",children:[e.jsx(o,{children:e.jsx(d,{children:e.jsxs("div",{className:"flex flex-wrap items-center justify-between gap-3",children:[e.jsxs("div",{className:"flex items-center gap-3",children:[e.jsx(ae,{className:"h-5 w-5 text-muted-foreground"}),e.jsx("h2",{className:"text-lg font-semibold",children:s.invoice_number}),e.jsx(Z,{status:s.status}),e.jsx(J,{variant:"secondary",children:Ne[s.type]??s.type})]}),e.jsxs("div",{className:"flex items-center gap-2 text-sm text-muted-foreground",children:[e.jsx(ie,{className:"h-4 w-4"}),e.jsx("span",{children:_(s.created_at)})]})]})})}),e.jsxs("div",{className:"grid gap-6 md:grid-cols-2",children:[e.jsxs(o,{children:[e.jsx(d,{children:e.jsxs(p,{className:"flex items-center gap-2",children:[e.jsx(re,{className:"h-4 w-4"}),t("invoiceDetail.customer")]})}),e.jsxs(x,{className:"space-y-2",children:[e.jsxs("div",{children:[e.jsx("p",{className:"text-sm text-muted-foreground",children:t("invoiceDetail.name")}),e.jsx("p",{className:"font-medium",children:s.customer_name})]}),e.jsxs("div",{children:[e.jsx("p",{className:"text-sm text-muted-foreground",children:t("invoiceDetail.email")}),e.jsx("p",{children:s.customer_email})]}),e.jsxs("div",{children:[e.jsx("p",{className:"text-sm text-muted-foreground",children:t("invoiceDetail.phone")}),e.jsx("p",{children:s.customer_phone})]})]})]}),e.jsxs(o,{children:[e.jsx(d,{children:e.jsxs(p,{className:"flex items-center gap-2",children:[e.jsx(ne,{className:"h-4 w-4"}),t("invoiceDetail.car")]})}),e.jsxs(x,{className:"space-y-2",children:[e.jsxs("div",{children:[e.jsx("p",{className:"text-sm text-muted-foreground",children:t("invoiceDetail.title")}),e.jsx(z,{to:`/cars/${s.car_id}`,className:"font-medium text-primary hover:underline",children:s.car_title})]}),e.jsxs("div",{children:[e.jsx("p",{className:"text-sm text-muted-foreground",children:t("invoiceDetail.vin")}),e.jsx("p",{className:"font-mono text-sm",children:s.car_vin})]})]})]})]}),e.jsxs(o,{children:[e.jsx(d,{children:e.jsx(p,{children:t("invoiceDetail.items")})}),e.jsx(x,{children:e.jsxs(X,{children:[e.jsx(ee,{children:e.jsxs(w,{children:[e.jsx(g,{className:"w-[50%]",children:t("invoiceDetail.description")}),e.jsx(g,{className:"text-right",children:t("invoiceDetail.qty")}),e.jsx(g,{className:"text-right",children:t("invoiceDetail.unitPrice")}),e.jsx(g,{className:"text-right",children:t("invoiceDetail.total")})]})}),e.jsx(se,{children:s.items.map(r=>e.jsxs(w,{children:[e.jsx(h,{children:r.description}),e.jsx(h,{className:"text-right tabular-nums",children:r.quantity}),e.jsx(h,{className:"text-right tabular-nums",children:n(r.unit_price)}),e.jsx(h,{className:"text-right tabular-nums font-medium",children:n(r.total)})]},r.id))}),e.jsx(te,{children:e.jsxs(w,{children:[e.jsx(h,{colSpan:3,className:"text-right font-medium",children:t("invoiceDetail.subtotal")}),e.jsx(h,{className:"text-right tabular-nums font-medium",children:n(s.subtotal)})]})})]})})]}),e.jsxs(o,{children:[e.jsx(d,{children:e.jsx(p,{children:t("invoiceDetail.financialSummary")})}),e.jsx(x,{children:e.jsxs("div",{className:"space-y-2",children:[e.jsxs("div",{className:"flex justify-between text-sm",children:[e.jsx("span",{className:"text-muted-foreground",children:t("invoiceDetail.subtotal")}),e.jsx("span",{className:"tabular-nums",children:n(s.subtotal)})]}),e.jsxs("div",{className:"flex justify-between text-sm",children:[e.jsx("span",{className:"text-muted-foreground",children:t("invoiceDetail.dealerFee")}),e.jsx("span",{className:"tabular-nums",children:n(s.dealer_fee)})]}),e.jsxs("div",{className:"flex justify-between text-sm",children:[e.jsx("span",{className:"text-muted-foreground",children:t("invoiceDetail.commission")}),e.jsx("span",{className:"tabular-nums",children:n(s.commission)})]}),e.jsx(Q,{}),e.jsxs("div",{className:"flex justify-between text-base font-semibold",children:[e.jsx("span",{children:t("invoiceDetail.total")}),e.jsx("span",{className:"tabular-nums",children:n(s.total)})]}),e.jsxs("div",{className:"flex justify-between text-sm",children:[e.jsx("span",{className:"text-muted-foreground",children:t("invoiceDetail.amountPaid")}),e.jsx("span",{className:"tabular-nums text-green-600 dark:text-green-400",children:n(s.amount_paid)})]}),e.jsxs("div",{className:"flex justify-between text-sm",children:[e.jsx("span",{className:"text-muted-foreground",children:t("invoiceDetail.balanceDue")}),e.jsx("span",{className:`tabular-nums font-semibold ${s.balance_due>0?"text-red-600 dark:text-red-400":"text-muted-foreground"}`,children:n(s.balance_due)})]}),s.due_date&&e.jsxs("div",{className:"flex justify-between text-sm",children:[e.jsx("span",{className:"text-muted-foreground",children:t("invoiceDetail.dueDate")}),e.jsx("span",{children:_(s.due_date)})]}),s.paid_at&&e.jsxs("div",{className:"flex justify-between text-sm",children:[e.jsx("span",{className:"text-muted-foreground",children:t("invoiceDetail.paidAt")}),e.jsx("span",{children:_(s.paid_at)})]})]})})]}),s.receipt_image&&e.jsxs(o,{children:[e.jsx(d,{children:e.jsxs(p,{className:"flex items-center gap-2",children:[e.jsx(le,{className:"h-4 w-4"}),t("invoiceDetail.receipt")]})}),e.jsx(x,{children:e.jsx("img",{src:s.receipt_image,alt:"Receipt",className:"max-w-sm rounded-lg border"})})]}),s.notes&&e.jsxs(o,{children:[e.jsx(d,{children:e.jsxs(p,{className:"flex items-center gap-2",children:[e.jsx(ce,{className:"h-4 w-4"}),t("invoiceDetail.notes")]})}),e.jsx(x,{children:e.jsx("p",{className:"whitespace-pre-wrap text-sm text-muted-foreground",children:s.notes})})]}),s.author_name&&e.jsxs("div",{className:"text-sm text-muted-foreground",children:[t("invoiceDetail.createdBy")," ",e.jsx("span",{className:"font-medium text-foreground",children:s.author_name})]})]})}function l(s){return new Intl.NumberFormat("en-US",{style:"currency",currency:"USD",minimumFractionDigits:2}).format(s)}function D(s){return s?B(new Date(s),"MMM d, yyyy"):""}const ye={standard:"Invoice",proforma:"Proforma Invoice",credit_note:"Credit Note"},we={paid:"Paid",partially_paid:"Partially Paid",unpaid:"Unpaid",cancelled:"Cancelled"},L=m.forwardRef(({invoice:s},t)=>{const r=s.balance_due>0,u=ye[s.type]??"Invoice";return e.jsxs("div",{ref:t,className:"ip",children:[e.jsx("style",{children:`
          /*
           * Print stylesheet for the invoice document.
           *
           * Design intent: match the dashboard's restrained, blue-accented
           * look — same blue primary (#3468b3 area), same rounded corners
           * (10px), same neutral slate text. No heavy gradients, no
           * hard-coded gray brand mark. Typography stack starts with
           * Geist (Latin/numerics) and falls through to Noto Sans
           * Georgian for Georgian characters.
           */
          .ip {
            font-family: 'Geist Variable', 'Noto Sans Georgian Variable',
                         -apple-system, 'Segoe UI', system-ui, sans-serif;
            color: #1e293b;
            background: #ffffff;
            max-width: 820px;
            margin: 0 auto;
            padding: 56px 64px 64px;
            font-size: 13px;
            line-height: 1.55;
            font-feature-settings: 'tnum', 'lnum';
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
          }
          .ip *, .ip *::before, .ip *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
          }

          /* ── Header — brand left, doc info right ─────────────────────── */
          .ip-hdr {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 28px;
            margin-bottom: 32px;
            border-bottom: 1px solid #e2e8f0;
          }
          .ip-brand-text {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.4px;
            line-height: 1.1;
          }
          .ip-brand-tag {
            font-size: 11px;
            color: #64748b;
            font-weight: 500;
            margin-top: 4px;
            letter-spacing: 0.2px;
          }

          .ip-doc {
            text-align: right;
          }
          .ip-doc-type {
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1.6px;
          }
          .ip-doc-num {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.3px;
            margin-top: 4px;
            font-variant-numeric: tabular-nums;
          }
          .ip-doc-pill {
            display: inline-block;
            margin-top: 10px;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.2px;
            border: 1px solid transparent;
          }
          .ip-doc-pill-paid           { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
          .ip-doc-pill-partially_paid { background: #fef3c7; color: #92400e; border-color: #fde68a; }
          .ip-doc-pill-unpaid         { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
          .ip-doc-pill-cancelled      { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }

          /* ── Meta grid — dates + parties in 2x2 ──────────────────────── */
          .ip-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px 56px;
            margin-bottom: 32px;
          }
          .ip-meta-block {
          }
          .ip-meta-block.right {
            text-align: right;
          }
          .ip-meta-lbl {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.4px;
            color: #94a3b8;
            margin-bottom: 6px;
          }
          .ip-meta-val {
            font-size: 14px;
            font-weight: 600;
            color: #0f172a;
            line-height: 1.5;
          }
          .ip-meta-sub {
            font-size: 12px;
            color: #64748b;
            margin-top: 2px;
            line-height: 1.5;
          }

          /* ── Vehicle row ─────────────────────────────────────────────── */
          .ip-car {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 20px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            margin-bottom: 28px;
          }
          .ip-car-icon {
            width: 36px;
            height: 36px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #3468b3;
            flex-shrink: 0;
          }
          .ip-car-icon svg { width: 20px; height: 20px; }
          .ip-car-title { font-size: 14px; font-weight: 600; color: #0f172a; }
          .ip-car-vin {
            font-family: 'SF Mono', 'JetBrains Mono', 'Fira Code', Consolas, monospace;
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
            letter-spacing: 0.6px;
          }

          /* ── Items table ─────────────────────────────────────────────── */
          .ip-tbl {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 24px;
          }
          .ip-tbl thead th {
            padding: 10px 14px;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: #64748b;
            text-align: left;
            border-bottom: 1.5px solid #cbd5e1;
            background: transparent;
          }
          .ip-tbl thead th.r { text-align: right; }
          .ip-tbl tbody td {
            padding: 14px;
            font-size: 13px;
            color: #475569;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
          }
          .ip-tbl tbody td.r {
            text-align: right;
            font-variant-numeric: tabular-nums;
          }
          .ip-tbl tbody td.name {
            font-weight: 500;
            color: #1e293b;
          }
          .ip-tbl tbody td.amt {
            font-weight: 600;
            color: #0f172a;
            text-align: right;
            font-variant-numeric: tabular-nums;
          }
          .ip-tbl tbody td.num {
            color: #94a3b8;
            font-weight: 600;
            width: 36px;
            font-variant-numeric: tabular-nums;
          }
          .ip-tbl tbody tr:last-child td { border-bottom: none; }

          /* ── Totals — right-aligned, no card background ──────────────── */
          .ip-totals-wrap {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 40px;
          }
          .ip-totals {
            width: 320px;
          }
          .ip-t-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            padding: 7px 0;
            font-size: 13px;
          }
          .ip-t-lbl { color: #64748b; font-weight: 500; }
          .ip-t-val {
            color: #1e293b;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
          }
          .ip-t-sep {
            border: none;
            height: 1px;
            background: #e2e8f0;
            margin: 6px 0;
          }
          .ip-t-grand { padding: 10px 0 6px; }
          .ip-t-grand .ip-t-lbl { font-size: 13px; font-weight: 600; color: #0f172a; }
          .ip-t-grand .ip-t-val { font-size: 18px; font-weight: 700; color: #0f172a; letter-spacing: -0.2px; }
          .ip-t-paid .ip-t-val { color: #166534; }
          .ip-t-due  .ip-t-val { color: #991b1b; font-weight: 700; font-size: 14px; }
          .ip-t-due-clear .ip-t-val { color: #166534; font-weight: 700; }

          /* ── Notes (optional) ────────────────────────────────────────── */
          .ip-notes {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 32px;
            font-size: 12px;
            color: #475569;
            line-height: 1.6;
          }
          .ip-notes-lbl {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.4px;
            color: #94a3b8;
            margin-bottom: 6px;
          }

          /* ── Footer ──────────────────────────────────────────────────── */
          .ip-foot {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding-top: 20px;
            margin-top: 8px;
            border-top: 1px solid #e2e8f0;
          }
          .ip-foot-msg {
            font-size: 14px;
            font-weight: 600;
            color: #0f172a;
            letter-spacing: -0.2px;
          }
          .ip-foot-co {
            text-align: right;
            font-size: 11px;
            color: #94a3b8;
            line-height: 1.7;
          }

          /* ── Print rules ─────────────────────────────────────────────── */
          @media print {
            .ip {
              padding: 32px 28px;
              max-width: 100%;
              font-size: 12px;
            }
            @page {
              size: A4;
              margin: 14mm 14mm 16mm 14mm;
            }
          }
        `}),e.jsxs("div",{className:"ip-hdr",children:[e.jsxs("div",{children:[e.jsx("div",{className:"ip-brand-text",children:"Prime Auto"}),e.jsx("div",{className:"ip-brand-tag",children:"Auto Import & Sales · Tbilisi, Georgia"})]}),e.jsxs("div",{className:"ip-doc",children:[e.jsx("div",{className:"ip-doc-type",children:u}),e.jsx("div",{className:"ip-doc-num",children:s.invoice_number}),e.jsx("span",{className:`ip-doc-pill ip-doc-pill-${s.status}`,children:we[s.status]??s.status})]})]}),e.jsxs("div",{className:"ip-meta",children:[e.jsxs("div",{className:"ip-meta-block",children:[e.jsx("div",{className:"ip-meta-lbl",children:"Issued"}),e.jsx("div",{className:"ip-meta-val",children:D(s.created_at)}),s.due_date&&e.jsxs(e.Fragment,{children:[e.jsx("div",{className:"ip-meta-lbl",style:{marginTop:14},children:"Due"}),e.jsx("div",{className:"ip-meta-val",children:D(s.due_date)})]}),s.paid_at&&e.jsxs(e.Fragment,{children:[e.jsx("div",{className:"ip-meta-lbl",style:{marginTop:14},children:"Paid"}),e.jsx("div",{className:"ip-meta-val",children:D(s.paid_at)})]})]}),e.jsxs("div",{className:"ip-meta-block right",children:[e.jsx("div",{className:"ip-meta-lbl",children:"Bill To"}),e.jsx("div",{className:"ip-meta-val",children:s.customer_name}),s.customer_email&&e.jsx("div",{className:"ip-meta-sub",children:s.customer_email}),s.customer_phone&&e.jsx("div",{className:"ip-meta-sub",children:s.customer_phone})]})]}),(s.car_title||s.car_vin)&&e.jsxs("div",{className:"ip-car",children:[e.jsx("div",{className:"ip-car-icon",children:e.jsxs("svg",{viewBox:"0 0 24 24",fill:"none",stroke:"currentColor",strokeWidth:"1.8",strokeLinecap:"round",strokeLinejoin:"round",children:[e.jsx("path",{d:"M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"}),e.jsx("circle",{cx:"7",cy:"17",r:"2"}),e.jsx("path",{d:"M9 17h6"}),e.jsx("circle",{cx:"17",cy:"17",r:"2"})]})}),e.jsxs("div",{children:[s.car_title&&e.jsx("div",{className:"ip-car-title",children:s.car_title}),s.car_vin&&e.jsxs("div",{className:"ip-car-vin",children:["VIN ",s.car_vin]})]})]}),e.jsxs("table",{className:"ip-tbl",children:[e.jsx("thead",{children:e.jsxs("tr",{children:[e.jsx("th",{children:"#"}),e.jsx("th",{children:"Description"}),e.jsx("th",{className:"r",children:"Qty"}),e.jsx("th",{className:"r",children:"Unit Price"}),e.jsx("th",{className:"r",children:"Amount"})]})}),e.jsx("tbody",{children:s.items.map((c,f)=>e.jsxs("tr",{children:[e.jsx("td",{className:"num",children:String(f+1).padStart(2,"0")}),e.jsx("td",{className:"name",children:c.description||"—"}),e.jsx("td",{className:"r",children:c.quantity}),e.jsx("td",{className:"r",children:l(c.unit_price)}),e.jsx("td",{className:"amt",children:l(c.total)})]},c.id||f))})]}),e.jsx("div",{className:"ip-totals-wrap",children:e.jsxs("div",{className:"ip-totals",children:[e.jsxs("div",{className:"ip-t-row",children:[e.jsx("span",{className:"ip-t-lbl",children:"Subtotal"}),e.jsx("span",{className:"ip-t-val",children:l(s.subtotal)})]}),s.dealer_fee>0&&e.jsxs("div",{className:"ip-t-row",children:[e.jsx("span",{className:"ip-t-lbl",children:"Dealer Fee"}),e.jsx("span",{className:"ip-t-val",children:l(s.dealer_fee)})]}),s.commission>0&&e.jsxs("div",{className:"ip-t-row",children:[e.jsx("span",{className:"ip-t-lbl",children:"Commission"}),e.jsx("span",{className:"ip-t-val",children:l(s.commission)})]}),e.jsx("hr",{className:"ip-t-sep"}),e.jsxs("div",{className:"ip-t-row ip-t-grand",children:[e.jsx("span",{className:"ip-t-lbl",children:"Total"}),e.jsx("span",{className:"ip-t-val",children:l(s.total)})]}),e.jsx("hr",{className:"ip-t-sep"}),e.jsxs("div",{className:"ip-t-row ip-t-paid",children:[e.jsx("span",{className:"ip-t-lbl",children:"Amount Paid"}),e.jsx("span",{className:"ip-t-val",children:l(s.amount_paid)})]}),e.jsxs("div",{className:`ip-t-row ${r?"ip-t-due":"ip-t-due-clear"}`,children:[e.jsx("span",{className:"ip-t-lbl",children:"Balance Due"}),e.jsx("span",{className:"ip-t-val",children:l(s.balance_due)})]})]})}),s.notes&&s.notes.trim()&&e.jsxs("div",{className:"ip-notes",children:[e.jsx("div",{className:"ip-notes-lbl",children:"Notes"}),e.jsx("div",{children:s.notes})]}),e.jsxs("div",{className:"ip-foot",children:[e.jsx("div",{className:"ip-foot-msg",children:"Thank you for your business."}),e.jsxs("div",{className:"ip-foot-co",children:["Prime Auto LLC",e.jsx("br",{}),"Auto Import & Sales",e.jsx("br",{}),"Tbilisi, Georgia"]})]})]})});L.displayName="InvoicePrint";function Oe(){const s=F(),{id:t}=q(),r=W(),u=y(a=>a.getInvoiceById)(t??""),c=y(a=>a.deleteInvoice),f=y(a=>a.updateInvoice),[M,A]=m.useState(null),[H,k]=m.useState(!1),[O,b]=m.useState(!1),N=m.useRef(null),i=u??M??void 0;m.useEffect(()=>{!u&&t&&Y.getInvoiceById(t).then(A).catch(()=>{})},[t,u]);const R=m.useCallback(()=>{if(!N.current)return;const a=window.open("","_blank");if(!a){v.error(s("invoices.popupBlocked"));return}a.document.write(`
      <!DOCTYPE html>
      <html>
        <head>
          <title>${i?.invoice_number??"Invoice"} - Prime Auto</title>
          <style>
            @page { margin: 12mm 8mm; size: A4; }
            html, body { margin: 0; padding: 0; background: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
          </style>
        </head>
        <body>${N.current.innerHTML}</body>
      </html>
    `),a.document.close(),a.focus(),setTimeout(()=>{a.print(),a.close()},250)},[i?.invoice_number]);if(!i)return e.jsxs("div",{className:"flex flex-col items-center justify-center gap-4 py-20",children:[e.jsx("h2",{className:"text-xl font-semibold",children:s("invoices.notFound")}),e.jsx("p",{className:"text-sm text-muted-foreground",children:s("invoices.notFoundDescription")}),e.jsx(z,{to:"/invoices",children:e.jsxs(j,{variant:"outline",children:[e.jsx(oe,{className:"mr-2 h-4 w-4"}),s("invoices.backToInvoices")]})})]});function U(){t&&(c(t),v.success(s("invoices.deleteSuccess")),r("/invoices"))}function E(a){const I=a.items,C=I.reduce(($,V)=>$+V.total,0),T=C+(Number(a.dealer_fee)||0)+(Number(a.commission)||0),S=Number(a.amount_paid)||0,G=Math.max(T-S,0),P=a.status;f(i.id,{type:a.type,status:P,car_id:a.car_id,car_vin:a.car_vin,car_title:a.car_title,customer_name:a.customer_name,customer_email:a.customer_email,customer_phone:a.customer_phone,items:I,subtotal:C,dealer_fee:Number(a.dealer_fee)||0,dealer_fee_paid:a.dealer_fee_paid,commission:Number(a.commission)||0,commission_paid:a.commission_paid,total:T,amount_paid:S,balance_due:G,paid_at:P==="paid"?new Date().toISOString():i.paid_at}),v.success(s("invoices.updateSuccess")),b(!1)}return e.jsxs("div",{className:"space-y-6",children:[e.jsx(K,{title:i.invoice_number,actions:e.jsxs(e.Fragment,{children:[e.jsxs(j,{variant:"outline",onClick:R,children:[e.jsx(de,{className:"mr-2 h-4 w-4"}),s("invoices.print")]}),e.jsxs(j,{variant:"outline",onClick:()=>b(!0),children:[e.jsx(me,{className:"mr-2 h-4 w-4"}),s("invoices.edit")]}),e.jsxs(j,{variant:"destructive",onClick:()=>k(!0),children:[e.jsx(pe,{className:"mr-2 h-4 w-4"}),s("invoices.delete")]})]})}),e.jsx(ve,{invoice:i}),e.jsx("div",{className:"hidden",children:e.jsx(L,{ref:N,invoice:i})}),e.jsx(he,{open:H,onOpenChange:k,title:s("invoices.deleteTitle"),description:s("invoices.deleteConfirm"),confirmLabel:s("invoices.delete"),onConfirm:U,variant:"destructive"}),e.jsx(ue,{open:O,onOpenChange:b,children:e.jsxs(fe,{className:"sm:max-w-5xl max-h-[90vh] overflow-y-auto",children:[e.jsxs(je,{children:[e.jsx(ge,{children:s("invoices.editInvoice")}),e.jsx(be,{children:s("invoices.editDescription")})]}),e.jsx(xe,{onSubmit:E,initialData:i},i.id)]})})]})}export{Oe as default};
