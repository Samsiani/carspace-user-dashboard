import{j as e,L as z,r as p,m as V,d as W}from"./react-vendor-C3ryBqjY.js";import{e as F,B as Q,E as Y,j as Z,F as v,l as j}from"./main-CTKaprPB.js";import{I as J,u as y}from"./InvoiceStatusBadge-oq7jqp-M.js";import{P as K}from"./PageHeader-CkoMRLEt.js";import{C as o,b as d,c as m,a as x}from"./card-Bx39QNf0.js";import{T as X,a as ee,b as w,c as g,d as se,e as h,f as te}from"./table-BxcJ91U4.js";import{F as ie,a1 as ae,l as re,C as ne,R as le,Z as ce,a2 as oe,a3 as de,N as pe,a4 as me}from"./icons-DEA80qAW.js";import{f as L}from"./format-BERtmEkv.js";import{I as xe}from"./InvoiceForm-DLZK1okO.js";import{C as he}from"./ConfirmDialog-CZkQ4_VX.js";import{D as ue,a as fe,b as je,c as ge,d as be}from"./dialog-sAurrkAA.js";import"./zod-BjsLooCQ.js";import"./index.esm-BmY8NfSR.js";import"./textarea-BCrMKZ0u.js";import"./select-CeYB8S7M.js";function n(s){return new Intl.NumberFormat("en-US",{style:"currency",currency:"USD",minimumFractionDigits:0,maximumFractionDigits:0}).format(s)}function _(s){return s?L(new Date(s),"MMM d, yyyy"):"-"}const Ne={standard:"Standard",proforma:"Proforma",credit_note:"Credit Note"};function ve({invoice:s}){const t=F();return e.jsxs("div",{className:"space-y-6",children:[e.jsx(o,{children:e.jsx(d,{children:e.jsxs("div",{className:"flex flex-wrap items-center justify-between gap-3",children:[e.jsxs("div",{className:"flex items-center gap-3",children:[e.jsx(ie,{className:"h-5 w-5 text-muted-foreground"}),e.jsx("h2",{className:"text-lg font-semibold",children:s.invoice_number}),e.jsx(J,{status:s.status}),e.jsx(Q,{variant:"secondary",children:Ne[s.type]??s.type})]}),e.jsxs("div",{className:"flex items-center gap-2 text-sm text-muted-foreground",children:[e.jsx(ae,{className:"h-4 w-4"}),e.jsx("span",{children:_(s.created_at)})]})]})})}),e.jsxs("div",{className:"grid gap-6 md:grid-cols-2",children:[e.jsxs(o,{children:[e.jsx(d,{children:e.jsxs(m,{className:"flex items-center gap-2",children:[e.jsx(re,{className:"h-4 w-4"}),t("invoiceDetail.customer")]})}),e.jsxs(x,{className:"space-y-2",children:[e.jsxs("div",{children:[e.jsx("p",{className:"text-sm text-muted-foreground",children:t("invoiceDetail.name")}),e.jsx("p",{className:"font-medium",children:s.customer_name})]}),e.jsxs("div",{children:[e.jsx("p",{className:"text-sm text-muted-foreground",children:t("invoiceDetail.email")}),e.jsx("p",{children:s.customer_email})]}),e.jsxs("div",{children:[e.jsx("p",{className:"text-sm text-muted-foreground",children:t("invoiceDetail.phone")}),e.jsx("p",{children:s.customer_phone})]})]})]}),e.jsxs(o,{children:[e.jsx(d,{children:e.jsxs(m,{className:"flex items-center gap-2",children:[e.jsx(ne,{className:"h-4 w-4"}),t("invoiceDetail.car")]})}),e.jsxs(x,{className:"space-y-2",children:[e.jsxs("div",{children:[e.jsx("p",{className:"text-sm text-muted-foreground",children:t("invoiceDetail.title")}),e.jsx(z,{to:`/cars/${s.car_id}`,className:"font-medium text-primary hover:underline",children:s.car_title})]}),e.jsxs("div",{children:[e.jsx("p",{className:"text-sm text-muted-foreground",children:t("invoiceDetail.vin")}),e.jsx("p",{className:"font-mono text-sm",children:s.car_vin})]})]})]})]}),e.jsxs(o,{children:[e.jsx(d,{children:e.jsx(m,{children:t("invoiceDetail.items")})}),e.jsx(x,{children:e.jsxs(X,{children:[e.jsx(ee,{children:e.jsxs(w,{children:[e.jsx(g,{className:"w-[50%]",children:t("invoiceDetail.description")}),e.jsx(g,{className:"text-right",children:t("invoiceDetail.qty")}),e.jsx(g,{className:"text-right",children:t("invoiceDetail.unitPrice")}),e.jsx(g,{className:"text-right",children:t("invoiceDetail.total")})]})}),e.jsx(se,{children:s.items.map(r=>e.jsxs(w,{children:[e.jsx(h,{children:r.description}),e.jsx(h,{className:"text-right tabular-nums",children:r.quantity}),e.jsx(h,{className:"text-right tabular-nums",children:n(r.unit_price)}),e.jsx(h,{className:"text-right tabular-nums font-medium",children:n(r.total)})]},r.id))}),e.jsx(te,{children:e.jsxs(w,{children:[e.jsx(h,{colSpan:3,className:"text-right font-medium",children:t("invoiceDetail.subtotal")}),e.jsx(h,{className:"text-right tabular-nums font-medium",children:n(s.subtotal)})]})})]})})]}),e.jsxs(o,{children:[e.jsx(d,{children:e.jsx(m,{children:t("invoiceDetail.financialSummary")})}),e.jsx(x,{children:e.jsxs("div",{className:"space-y-2",children:[e.jsxs("div",{className:"flex justify-between text-sm",children:[e.jsx("span",{className:"text-muted-foreground",children:t("invoiceDetail.subtotal")}),e.jsx("span",{className:"tabular-nums",children:n(s.subtotal)})]}),e.jsxs("div",{className:"flex justify-between text-sm",children:[e.jsx("span",{className:"text-muted-foreground",children:t("invoiceDetail.dealerFee")}),e.jsx("span",{className:"tabular-nums",children:n(s.dealer_fee)})]}),e.jsxs("div",{className:"flex justify-between text-sm",children:[e.jsx("span",{className:"text-muted-foreground",children:t("invoiceDetail.commission")}),e.jsx("span",{className:"tabular-nums",children:n(s.commission)})]}),e.jsx(Y,{}),e.jsxs("div",{className:"flex justify-between text-base font-semibold",children:[e.jsx("span",{children:t("invoiceDetail.total")}),e.jsx("span",{className:"tabular-nums",children:n(s.total)})]}),e.jsxs("div",{className:"flex justify-between text-sm",children:[e.jsx("span",{className:"text-muted-foreground",children:t("invoiceDetail.amountPaid")}),e.jsx("span",{className:"tabular-nums text-green-600 dark:text-green-400",children:n(s.amount_paid)})]}),e.jsxs("div",{className:"flex justify-between text-sm",children:[e.jsx("span",{className:"text-muted-foreground",children:t("invoiceDetail.balanceDue")}),e.jsx("span",{className:`tabular-nums font-semibold ${s.balance_due>0?"text-red-600 dark:text-red-400":"text-muted-foreground"}`,children:n(s.balance_due)})]}),s.due_date&&e.jsxs("div",{className:"flex justify-between text-sm",children:[e.jsx("span",{className:"text-muted-foreground",children:t("invoiceDetail.dueDate")}),e.jsx("span",{children:_(s.due_date)})]}),s.paid_at&&e.jsxs("div",{className:"flex justify-between text-sm",children:[e.jsx("span",{className:"text-muted-foreground",children:t("invoiceDetail.paidAt")}),e.jsx("span",{children:_(s.paid_at)})]})]})})]}),s.receipt_image&&e.jsxs(o,{children:[e.jsx(d,{children:e.jsxs(m,{className:"flex items-center gap-2",children:[e.jsx(le,{className:"h-4 w-4"}),t("invoiceDetail.receipt")]})}),e.jsx(x,{children:e.jsx("img",{src:s.receipt_image,alt:"Receipt",className:"max-w-sm rounded-lg border"})})]}),s.notes&&e.jsxs(o,{children:[e.jsx(d,{children:e.jsxs(m,{className:"flex items-center gap-2",children:[e.jsx(ce,{className:"h-4 w-4"}),t("invoiceDetail.notes")]})}),e.jsx(x,{children:e.jsx("p",{className:"whitespace-pre-wrap text-sm text-muted-foreground",children:s.notes})})]}),s.author_name&&e.jsxs("div",{className:"text-sm text-muted-foreground",children:[t("invoiceDetail.createdBy")," ",e.jsx("span",{className:"font-medium text-foreground",children:s.author_name})]})]})}function l(s){return new Intl.NumberFormat("en-US",{style:"currency",currency:"USD",minimumFractionDigits:2}).format(s)}function D(s){return s?L(new Date(s),"MMMM d, yyyy"):""}const ye={standard:"Invoice",proforma:"Proforma Invoice",credit_note:"Credit Note"},we={paid:"Paid",partially_paid:"Partially Paid",unpaid:"Unpaid",cancelled:"Cancelled"},_e='<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/></svg>',M=p.forwardRef(({invoice:s},t)=>{const r=s.balance_due>0,u=ye[s.type]??"Invoice";return e.jsxs("div",{ref:t,className:"ip",children:[e.jsx("style",{children:`
          .ip {
            font-family: 'Inter', -apple-system, 'Segoe UI', system-ui, sans-serif;
            color: #1e293b;
            background: #fff;
            max-width: 860px;
            margin: 0 auto;
            padding: 80px 72px;
            font-size: 14px;
            line-height: 1.6;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
          }
          .ip *, .ip *::before, .ip *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
          }

          /* ═══ Top strip — dates ═══ */
          .ip-top {
            display: flex;
            gap: 40px;
            margin-bottom: 40px;
            padding: 16px 24px;
            background: #f8fafc;
            border-radius: 10px;
          }
          .ip-top-item { }
          .ip-top-lbl {
            font-size: 9px; font-weight: 800;
            letter-spacing: 3px; text-transform: uppercase;
            color: #94a3b8; margin-bottom: 3px;
          }
          .ip-top-val {
            font-size: 13px; font-weight: 600; color: #0f172a;
          }

          /* ═══ Header ═══ */
          .ip-hdr {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 48px;
          }
          .ip-brand {
            display: flex;
            align-items: center;
            gap: 16px;
          }
          .ip-brand-mark {
            width: 52px; height: 52px;
            background: linear-gradient(135deg, #0f172a 0%, #334155 100%);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
          }
          .ip-brand-mark svg { width: 30px; height: 30px; }
          .ip-brand-text { font-size: 28px; font-weight: 800; color: #0f172a; letter-spacing: -0.8px; line-height: 1.1; }
          .ip-brand-tag { font-size: 12px; color: #94a3b8; font-weight: 500; margin-top: 3px; }

          .ip-doc { text-align: right; }
          .ip-doc-type { font-size: 32px; font-weight: 300; color: #0f172a; letter-spacing: -0.5px; line-height: 1.1; }
          .ip-doc-num { font-size: 15px; font-weight: 600; color: #64748b; margin-top: 8px; }
          .ip-doc-pill {
            display: inline-block; margin-top: 12px;
            padding: 5px 16px; border-radius: 100px;
            font-size: 12px; font-weight: 700; letter-spacing: 0.3px;
          }
          .ip-doc-pill-paid { background: #ecfdf5; color: #059669; }
          .ip-doc-pill-partially_paid { background: #fffbeb; color: #d97706; }
          .ip-doc-pill-unpaid { background: #fef2f2; color: #dc2626; }
          .ip-doc-pill-cancelled { background: #f8fafc; color: #94a3b8; }

          /* ═══ Parties — full width, from left, bill to right ═══ */
          .ip-parties {
            display: flex;
            justify-content: space-between;
            margin-bottom: 48px;
          }
          .ip-party { }
          .ip-party-right { text-align: right; }
          .ip-party-lbl {
            font-size: 10px; font-weight: 800;
            letter-spacing: 3px; text-transform: uppercase;
            color: #94a3b8; margin-bottom: 10px;
          }
          .ip-party-name {
            font-size: 17px; font-weight: 700; color: #0f172a; margin-bottom: 6px;
          }
          .ip-party-detail {
            font-size: 13px; color: #64748b; line-height: 1.8;
          }

          /* ═══ Divider ═══ */
          .ip-line {
            border: none; height: 1px; background: #e2e8f0;
            margin: 0 0 48px 0;
          }

          /* ═══ Vehicle ═══ */
          .ip-car {
            display: flex; align-items: center; gap: 18px;
            padding: 20px 28px;
            background: #f8fafc; border-radius: 14px;
            margin-bottom: 48px;
          }
          .ip-car-icon {
            width: 44px; height: 44px;
            background: #e2e8f0; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
          }
          .ip-car-icon svg { width: 24px; height: 24px; color: #64748b; }
          .ip-car-title { font-size: 16px; font-weight: 700; color: #0f172a; }
          .ip-car-vin {
            font-family: 'SF Mono', 'Fira Code', 'Consolas', monospace;
            font-size: 12px; color: #94a3b8; margin-top: 4px; letter-spacing: 1px;
          }

          /* ═══ Items table ═══ */
          .ip-tbl {
            width: 100%; border-collapse: separate; border-spacing: 0;
            margin-bottom: 12px;
          }
          .ip-tbl thead th {
            padding: 14px 20px;
            font-size: 10px; font-weight: 800;
            letter-spacing: 1.5px; text-transform: uppercase;
            color: #94a3b8; text-align: left;
            background: #f8fafc;
          }
          .ip-tbl thead th:first-child { border-radius: 12px 0 0 12px; }
          .ip-tbl thead th:last-child { border-radius: 0 12px 12px 0; }
          .ip-tbl thead th.r { text-align: right; }
          .ip-tbl tbody td {
            padding: 18px 20px;
            font-size: 14px; color: #475569;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
          }
          .ip-tbl tbody td.r {
            text-align: right;
            font-variant-numeric: tabular-nums;
          }
          .ip-tbl tbody td.name {
            font-weight: 600; color: #1e293b;
          }
          .ip-tbl tbody td.amt {
            font-weight: 700; color: #0f172a;
            text-align: right; font-variant-numeric: tabular-nums;
          }
          .ip-tbl tbody td.num {
            color: #cbd5e1; font-weight: 600; width: 48px;
          }
          .ip-tbl tbody tr:last-child td { border-bottom: none; }

          /* ═══ Totals ═══ */
          .ip-totals-wrap {
            display: flex; justify-content: flex-end;
            margin-bottom: 64px; margin-top: 12px;
          }
          .ip-totals {
            width: 340px;
            background: #f8fafc; border-radius: 14px;
            padding: 28px 32px;
          }
          .ip-t-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 8px 0; font-size: 14px;
          }
          .ip-t-lbl { color: #64748b; font-weight: 500; }
          .ip-t-val { font-variant-numeric: tabular-nums; color: #1e293b; font-weight: 600; }
          .ip-t-sep { border: none; height: 1px; background: #e2e8f0; margin: 8px 0; }

          .ip-t-grand { padding: 16px 0 12px; }
          .ip-t-grand .ip-t-lbl { font-size: 16px; font-weight: 800; color: #0f172a; }
          .ip-t-grand .ip-t-val { font-size: 24px; font-weight: 800; color: #0f172a; letter-spacing: -0.3px; }

          .ip-t-paid .ip-t-val { color: #059669; font-weight: 700; }
          .ip-t-due .ip-t-val { color: #dc2626; font-weight: 800; font-size: 16px; }
          .ip-t-due-clear .ip-t-val { color: #059669; font-weight: 700; }

          /* ═══ Footer ═══ */
          .ip-foot {
            display: flex; justify-content: space-between; align-items: flex-end;
            padding-top: 36px;
            border-top: 2px solid #0f172a;
          }
          .ip-foot-msg { font-size: 18px; font-weight: 700; color: #0f172a; letter-spacing: -0.3px; }
          .ip-foot-co { text-align: right; font-size: 12px; color: #94a3b8; line-height: 1.8; }
        `}),e.jsxs("div",{className:"ip-top",children:[e.jsxs("div",{className:"ip-top-item",children:[e.jsx("div",{className:"ip-top-lbl",children:"Issued"}),e.jsx("div",{className:"ip-top-val",children:D(s.created_at)})]}),s.due_date&&e.jsxs("div",{className:"ip-top-item",children:[e.jsx("div",{className:"ip-top-lbl",children:"Due Date"}),e.jsx("div",{className:"ip-top-val",children:D(s.due_date)})]}),s.paid_at&&e.jsxs("div",{className:"ip-top-item",children:[e.jsx("div",{className:"ip-top-lbl",children:"Date Paid"}),e.jsx("div",{className:"ip-top-val",children:D(s.paid_at)})]})]}),e.jsxs("div",{className:"ip-hdr",children:[e.jsxs("div",{className:"ip-brand",children:[e.jsx("div",{className:"ip-brand-mark",children:e.jsxs("svg",{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24",fill:"none",stroke:"white",strokeWidth:"1.8",strokeLinecap:"round",strokeLinejoin:"round",children:[e.jsx("path",{d:"M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"}),e.jsx("circle",{cx:"7",cy:"17",r:"2"}),e.jsx("path",{d:"M9 17h6"}),e.jsx("circle",{cx:"17",cy:"17",r:"2"})]})}),e.jsxs("div",{children:[e.jsx("div",{className:"ip-brand-text",children:"Prime Auto"}),e.jsx("div",{className:"ip-brand-tag",children:"Auto Import & Sales"})]})]}),e.jsxs("div",{className:"ip-doc",children:[e.jsx("div",{className:"ip-doc-type",children:u}),e.jsx("div",{className:"ip-doc-num",children:s.invoice_number}),e.jsx("span",{className:`ip-doc-pill ip-doc-pill-${s.status}`,children:we[s.status]??s.status})]})]}),e.jsxs("div",{className:"ip-parties",children:[e.jsxs("div",{className:"ip-party",children:[e.jsx("div",{className:"ip-party-lbl",children:"From"}),e.jsx("div",{className:"ip-party-name",children:"Prime Auto LLC"}),e.jsxs("div",{className:"ip-party-detail",children:["Auto Import & Sales",e.jsx("br",{}),"Tbilisi, Georgia"]})]}),e.jsxs("div",{className:"ip-party ip-party-right",children:[e.jsx("div",{className:"ip-party-lbl",children:"Bill To"}),e.jsx("div",{className:"ip-party-name",children:s.customer_name}),e.jsxs("div",{className:"ip-party-detail",children:[s.customer_email,e.jsx("br",{}),s.customer_phone]})]})]}),e.jsx("hr",{className:"ip-line"}),e.jsxs("div",{className:"ip-car",children:[e.jsx("div",{className:"ip-car-icon",dangerouslySetInnerHTML:{__html:_e}}),e.jsxs("div",{children:[e.jsx("div",{className:"ip-car-title",children:s.car_title}),e.jsxs("div",{className:"ip-car-vin",children:["VIN ",s.car_vin]})]})]}),e.jsxs("table",{className:"ip-tbl",children:[e.jsx("thead",{children:e.jsxs("tr",{children:[e.jsx("th",{children:"#"}),e.jsx("th",{children:"Description"}),e.jsx("th",{className:"r",children:"Qty"}),e.jsx("th",{className:"r",children:"Unit Price"}),e.jsx("th",{className:"r",children:"Amount"})]})}),e.jsx("tbody",{children:s.items.map((c,f)=>e.jsxs("tr",{children:[e.jsx("td",{className:"num",children:String(f+1).padStart(2,"0")}),e.jsx("td",{className:"name",children:c.description}),e.jsx("td",{className:"r",children:c.quantity}),e.jsx("td",{className:"r",children:l(c.unit_price)}),e.jsx("td",{className:"amt",children:l(c.total)})]},c.id||f))})]}),e.jsx("div",{className:"ip-totals-wrap",children:e.jsxs("div",{className:"ip-totals",children:[e.jsxs("div",{className:"ip-t-row",children:[e.jsx("span",{className:"ip-t-lbl",children:"Subtotal"}),e.jsx("span",{className:"ip-t-val",children:l(s.subtotal)})]}),s.dealer_fee>0&&e.jsxs("div",{className:"ip-t-row",children:[e.jsx("span",{className:"ip-t-lbl",children:"Dealer Fee"}),e.jsx("span",{className:"ip-t-val",children:l(s.dealer_fee)})]}),s.commission>0&&e.jsxs("div",{className:"ip-t-row",children:[e.jsx("span",{className:"ip-t-lbl",children:"Commission"}),e.jsx("span",{className:"ip-t-val",children:l(s.commission)})]}),e.jsx("hr",{className:"ip-t-sep"}),e.jsxs("div",{className:"ip-t-row ip-t-grand",children:[e.jsx("span",{className:"ip-t-lbl",children:"Total"}),e.jsx("span",{className:"ip-t-val",children:l(s.total)})]}),e.jsx("hr",{className:"ip-t-sep"}),e.jsxs("div",{className:"ip-t-row ip-t-paid",children:[e.jsx("span",{className:"ip-t-lbl",children:"Amount Paid"}),e.jsx("span",{className:"ip-t-val",children:l(s.amount_paid)})]}),e.jsxs("div",{className:`ip-t-row ${r?"ip-t-due":"ip-t-due-clear"}`,children:[e.jsx("span",{className:"ip-t-lbl",children:"Balance Due"}),e.jsx("span",{className:"ip-t-val",children:l(s.balance_due)})]})]})}),e.jsxs("div",{className:"ip-foot",children:[e.jsx("div",{className:"ip-foot-msg",children:"Thank you for your business."}),e.jsxs("div",{className:"ip-foot-co",children:["Prime Auto LLC",e.jsx("br",{}),"Auto Import & Sales",e.jsx("br",{}),"Tbilisi, Georgia"]})]})]})});M.displayName="InvoicePrint";function Re(){const s=F(),{id:t}=V(),r=W(),u=y(i=>i.getInvoiceById)(t??""),c=y(i=>i.deleteInvoice),f=y(i=>i.updateInvoice),[A,B]=p.useState(null),[H,k]=p.useState(!1),[O,b]=p.useState(!1),N=p.useRef(null),a=u??A??void 0;p.useEffect(()=>{!u&&t&&Z.getInvoiceById(t).then(B).catch(()=>{})},[t,u]);const R=p.useCallback(()=>{if(!N.current)return;const i=window.open("","_blank");if(!i){v.error(s("invoices.popupBlocked"));return}i.document.write(`
      <!DOCTYPE html>
      <html>
        <head>
          <title>${a?.invoice_number??"Invoice"} - Prime Auto</title>
          <style>
            @page { margin: 12mm 8mm; size: A4; }
            html, body { margin: 0; padding: 0; background: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
          </style>
        </head>
        <body>${N.current.innerHTML}</body>
      </html>
    `),i.document.close(),i.focus(),setTimeout(()=>{i.print(),i.close()},250)},[a?.invoice_number]);if(!a)return e.jsxs("div",{className:"flex flex-col items-center justify-center gap-4 py-20",children:[e.jsx("h2",{className:"text-xl font-semibold",children:s("invoices.notFound")}),e.jsx("p",{className:"text-sm text-muted-foreground",children:s("invoices.notFoundDescription")}),e.jsx(z,{to:"/invoices",children:e.jsxs(j,{variant:"outline",children:[e.jsx(oe,{className:"mr-2 h-4 w-4"}),s("invoices.backToInvoices")]})})]});function U(){t&&(c(t),v.success(s("invoices.deleteSuccess")),r("/invoices"))}function E(i){const I=i.items,C=I.reduce((q,G)=>q+G.total,0),S=C+(Number(i.dealer_fee)||0)+(Number(i.commission)||0),T=Number(i.amount_paid)||0,$=Math.max(S-T,0),P=i.status;f(a.id,{type:i.type,status:P,car_id:i.car_id,car_vin:i.car_vin,car_title:i.car_title,customer_name:i.customer_name,customer_email:i.customer_email,customer_phone:i.customer_phone,items:I,subtotal:C,dealer_fee:Number(i.dealer_fee)||0,dealer_fee_paid:i.dealer_fee_paid,commission:Number(i.commission)||0,commission_paid:i.commission_paid,total:S,amount_paid:T,balance_due:$,paid_at:P==="paid"?new Date().toISOString():a.paid_at}),v.success(s("invoices.updateSuccess")),b(!1)}return e.jsxs("div",{className:"space-y-6",children:[e.jsx(K,{title:a.invoice_number,actions:e.jsxs(e.Fragment,{children:[e.jsxs(j,{variant:"outline",onClick:R,children:[e.jsx(de,{className:"mr-2 h-4 w-4"}),s("invoices.print")]}),e.jsxs(j,{variant:"outline",onClick:()=>b(!0),children:[e.jsx(pe,{className:"mr-2 h-4 w-4"}),s("invoices.edit")]}),e.jsxs(j,{variant:"destructive",onClick:()=>k(!0),children:[e.jsx(me,{className:"mr-2 h-4 w-4"}),s("invoices.delete")]})]})}),e.jsx(ve,{invoice:a}),e.jsx("div",{className:"hidden",children:e.jsx(M,{ref:N,invoice:a})}),e.jsx(he,{open:H,onOpenChange:k,title:s("invoices.deleteTitle"),description:s("invoices.deleteConfirm"),confirmLabel:s("invoices.delete"),onConfirm:U,variant:"destructive"}),e.jsx(ue,{open:O,onOpenChange:b,children:e.jsxs(fe,{className:"sm:max-w-5xl max-h-[90vh] overflow-y-auto",children:[e.jsxs(je,{children:[e.jsx(ge,{children:s("invoices.editInvoice")}),e.jsx(be,{children:s("invoices.editDescription")})]}),e.jsx(xe,{onSubmit:E,initialData:a},a.id)]})})]})}export{Re as default};
