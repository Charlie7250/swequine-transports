# Page design prompts — image generation + build specs

Reference pack for redesigning South West Equine Services page by page. For each screen there are
**two** blocks:

1. **Image prompt** — paste into an image model (Ideogram / Imagen / Recraft recommended for legible
   UI text) to get a visual mockup.
2. **Build spec** — a structured, data-accurate brief. Feed it to the code-generation model
   **alongside** the image. The image carries the look; the spec carries the truth (real routes,
   fields, components, states, exclusions) so the generated components are wireable to the real app.

**Scope decision (locked): today's backend only.** Every screen here reflects data and actions that
exist in the app now. The dashboard mockup that seeded this work contained several features with no
backend — they are listed under *Global exclusions* and must NOT be reintroduced.

**Names:** signed-in operator is **Cliff Edwards**; business is **South West Equine Services**.

Source of truth for data/actions: `docs/horse-quotes/features/dashboard-prototype-design-contract.md`,
`docs/workflow/pricing-policy.md`, and the Blade views under `resources/views/`.

---

## How to use (recommended pipeline)

1. Generate the **Style reference sheet** (Step 0) first. Save the image.
2. For each page: generate its **image prompt**, attaching the Step 0 sheet as a style reference
   (`--sref` in Midjourney; "match the attached style" in Ideogram/Imagen). This keeps every page
   visually consistent — image models have no memory between generations.
3. Hand the **image + build spec** together to the code model.
4. Prefer **desktop 16:9** for all screens. The dashboard, transport-day detail, and issued quote
   also deserve a **mobile 9:16** variant later.

---

## Style preamble (prepend to EVERY image prompt)

> High-fidelity web UI for **South West Equine Services**, a private internal tool a horse-transport
> operator uses to quote and manage jobs. Calm, trustworthy, editorial — a premium business tool,
> not a consumer app, not flashy.
>
> **Colour:** deep navy sidebar (#0b1c2d→#10243a vertical gradient) with muted gold accents (#be9134,
> #e4c779) and a gold left-border on the active nav item. Main canvas warm cream/paper (#fbf9f4 /
> #fffefa) with a very faint gold radial glow top-right. Dark ink text (#18283c), muted grey
> secondary text (#687386). Cards are near-white (#fffefa) with hairline warm-stone borders (#e7e1d7)
> and soft low shadows (0 18px 45px rgba(17,31,49,0.08)). Status pills, dark text on soft tint:
> green = completed (#27643b on #e4f2e4), blue = booked (#275aa2 on #e4edfc), amber = pending
> (#8a5b05 on #fff1cf), grey = draft (#555f6d on #eceef1), red = lost (#8a3232 on #f7e5e2).
>
> **Type:** brand wordmark and all headings in a Georgia-style serif; body/UI in Instrument Sans
> (clean humanist sans). Generous whitespace, medium border-radius, restrained.
>
> **Left sidebar (every screen except Login and the printed Issued Quote):** dark navy, ~240px. Top:
> a gold line-art **horse-head wordmark** reading "South West / Equine Services" (serif). Nav items
> with small line icons: **Dashboard, Transport days, Loading practice**, then an **Administration**
> group heading with **Fuel prices, Rate settings, Operational evidence**. Bottom: a moody
> dark-toned **horse silhouette photo** and the tagline "SAFE JOURNEYS · HAPPIER HORSES" in small
> gold letter-spaced caps. Top bar: light, with the page title left and the primary action right.
>
> **Data:** realistic UK placeholder data only (see cast below). Legible text and numbers, no
> gibberish, no device frame, no cursor, no watermark.

### Global exclusions (never render — no backend exists)

Global search bar · notification bell/badge · "Optimise route" · revenue growth %/trend arrows ·
scheduled stop **times** (jobs store no times) · the word "Home" for depot (use **Depot** + postcode) ·
horse **names** · driver or vehicle assignment · a Customers / Horses / Vehicles / Reports / Calendar
page or nav item · map imagery. Journeys are identified by **UK postcodes**, not town/place names.

### Placeholder cast (reuse for consistency)

- **Operator (signed in):** Cliff Edwards · South West Equine Services.
- **Depot:** Taunton, **TA1 1AA**.
- **Customers & journeys:** Sarah Whitmore (TA1 1AA → EX4 3PL, 2 horses) · Mr & Mrs Carter
  (TA6 3NT → TQ13 9AA, 1 horse) · Emily Rogers (EX4 3PL → BS40 5QL, 1 horse) · Silverwood Equestrian
  (PL4 0EA → DT1 1AA, 2 horses).
- **Pricing anchors (from `pricing-policy.md`):** one-horse standard quote **£255.90**; two-horse
  **£282.27**; shared one-horse allocation **£240.87**; corrected-fuel **£259.39**. Standard legs
  10 / 90 / 96 miles. Unloaded rate £0.921292/mi; one-horse loaded £1.758282/mi; two-horse loaded
  £2.051329/mi. Fuel £1.5300/L (source "Texaco — manual entry"). Rate inputs: 22 mpg, 4.54 L/gallon,
  £0.05 maintenance/mi, unloaded add-on 0.5555555556, loaded add-on 0.8064516129, one-horse ×1.5,
  two-horse ×1.75, shared load 75%.
- **Statuses:** draft, quoted, pending, booked, completed, lost.
- **Routing provider:** HERE. Route legs: depot→pickup, pickup→drop-off, drop-off→depot.

---

## Step 0 — Style reference sheet (generate first)

**Image prompt:**

> [STYLE PREAMBLE] A **UI component reference sheet** (not a page) on a warm cream background, arranged
> as a tidy grid with small serif labels: the navy sidebar with gold horse-head wordmark and nav;
> primary gold button and outline button; the five status pills (draft, quoted/blue, pending/amber,
> booked/blue, completed/green, lost/red); a summary metric card (label, big serif value, caption); a
> data-table header row with three body rows; a text input, a select, and a checkbox row; a serif
> section heading with grey caption. Cohesive design system, flat, crisp, legible. 16:9.

**Use:** attach this image as the style reference on every page prompt below.

---

## 1. Login

**Image prompt:**

> [STYLE PREAMBLE — but NO sidebar on this screen] A centred sign-in card on the warm cream canvas
> with the faint gold glow. Card: small gold eyebrow "INTERNAL ACCESS"; serif H1 "Sign in to the
> quotes workspace"; a line of grey copy about transparent, revisioned pricing; an email field
> (cliff@southwestequine.co.uk), a password field, a "Keep me signed in on this device" checkbox, and
> a full-width gold "Sign in" button. Above the card, the gold horse-head "South West Equine Services"
> wordmark. Quiet, premium, lots of space. 16:9.

**Build spec:**
- Route `login` (`auth/login.blade.php`). No sidebar/top bar — standalone centred panel.
- Fields: `email` (required), `password` (required), `remember` checkbox. Submit "Sign in".
- Error state: a single red-tinted error banner above the form ("These credentials do not match our
  records.").
- Exclude: social login, sign-up link, password-reset (none exist).

---

## 2. New transport enquiry

**Image prompt:**

> [STYLE PREAMBLE] Page title "New transport enquiry" (serif) with grey caption "Record the customer
> and journey details before calculating the route." A note "Loading practice is separate from
> transport quoting." A two-column form card: Enquiry source (select: Direct contact / Social media /
> Word of mouth / HayNet / Other), Customer name (Sarah Whitmore), Email, Phone, Pickup postcode
> (TA1 1AA), Drop-off postcode (EX4 3PL), Horse count (2), Requested date. Below: a "Date to be
> arranged" checkbox, a "Special transport constraints" textarea, and a "No special constraints are
> known…" acknowledgement checkbox. Primary gold button "Resolve route and miles". 16:9.

**Build spec:**
- Route `quotes.create` → posts to `transport-enquiries.resolve` (`transport-enquiries/create.blade.php`).
- Fields (exact): `source` (select, 5 options above), `customer_name`, `email`, `phone`,
  `pickup_postcode`, `dropoff_postcode`, `horse_count` (number, min 1), `requested_date` (date),
  `date_to_be_arranged` (checkbox), `special_constraints` (textarea),
  `special_constraints_acknowledged` (checkbox). Hidden `submission_token`.
- Primary action: "Resolve route and miles". This is the front door of the quote flow.
- States: empty; filled; validation (per-field red outline + error list).
- Exclude: any manual leg-mile fields (mileage comes from routing, not this form).

---

## 3. Route review

**Image prompt:**

> [STYLE PREAMBLE] Page title "Review calculated route" with a status line "Route calculated from the
> entered postcodes." An enquiry card: customer Sarah Whitmore, a status pill "resolved", depot
> postcode TA1 1AA, route source "HERE", resolved timestamp. A "Route legs" table: Depot to pickup
> (TA1 1AA→TA1 1AA area, 10 miles), Pickup to drop-off (TA1 1AA→EX4 3PL, 90 miles), Drop-off to depot
> (96 miles). A primary gold button "Use this route for quote". 16:9.

**Build spec:**
- Route `transport-enquiries.route-review` (`transport-enquiries/route-review.blade.php`).
- Regions: status message; enquiry summary (customer, `overall_status` pill, depot postcode, provider
  HERE, resolved-at); Route-legs table (leg, from, to, miles); primary action "Use this route for
  quote" → `route-accept`.
- Key states to depict (one image each ideally):
  - **Resolved** (happy): accept button enabled.
  - **Manual-review required** (horse_count > 2): a calm panel "Automatic transport pricing supports
    one or two horses. Counts above two require manual review." and NO accept button. (This is the
    F1 behaviour — must be visible.)
  - **Exception** (unresolved / provider_unavailable): a "Location review" table + "Route issues"
    list + an outline "Handle route exception" button.
- Exclude: maps, driver/vehicle, times.

---

## 4. Handle route exception

**Image prompt:**

> [STYLE PREAMBLE] Page title "Handle route exception" with caption about keeping recorded route
> evidence. A "Location review" table (leg, entered origin, resolved origin, entered destination,
> resolved destination). A "Route issues" bullet list ("Pickup to drop-off: No reliable route was
> found for this route leg."). A "Retry or correct the route" card with an outline "Retry route
> lookup" button and a small form (Pickup postcode, Drop-off postcode, "Correct and resolve again").
> A "Use manual miles because route lookup is unavailable" card: Reason category select, Explanation
> textarea, three leg-mile inputs, primary "Apply manual route miles". 16:9.

**Build spec:**
- Route `transport-enquiries.route-exception` (`transport-enquiries/route-exception.blade.php`).
- Regions: Location review table; Route issues list; Recorded route values table; Retry form
  (`route-retry`); Correct form (`route-correct`: `pickup_postcode`, `dropoff_postcode`); Manual
  fallback form (`manual-fallback`, only when allowed): `reason_category` (Provider outage /
  Postcode ambiguity / Invalid provider response / Disputed mileage / Operational exception),
  `explanation`, three `route_legs[n][miles]` inputs.
- Emphasis: manual miles are an exception path, visually secondary to retry/correct.

---

## 5. Quote workspace (revision detail + pricing) — the core screen

**Image prompt:**

> [STYLE PREAMBLE] Page title "Revision 3 for Sarah Whitmore" (serif) with caption about keeping
> details, legs, pricing and status in one place. A row of three metric cards: Job status (pill
> "Draft"), Engine total £282.27, Final quoted total £282.27 ("No manual final total override
> recorded."). A "Quote actions" card with an Issue checklist (all "Ready"), a confirmation checkbox,
> a gold "Issue quote" button and an outline "Manage quote exceptions". A "Priced route legs" table:
> Depot to pickup / TA1 1AA / … / 10 / Unloaded / £0.921292 / £9.21; Pickup to drop-off / 90 / Loaded
> / £2.051329 / £184.62; Drop-off to depot / 96 / Unloaded / £0.921292 / £88.44. A collapsible
> "Calculation explanation" panel (fuel context, resolved rates, totals). Restrained, data-dense but
> calm. 16:9.

**Build spec:**
- Route `jobs.revisions.show` (`job-revisions/show.blade.php`). The richest screen; it embodies the
  product's "transparent pricing" promise — legibility of numbers matters most here.
- Regions in order: hero title (Revision N for {customer}); three metric cards (Job status pill,
  Engine total, Final quoted total + override reason); **Quote actions** (status-dependent — draft
  shows Issue checklist + confirm checkbox + "Issue quote" + "Manage quote exceptions"; quoted shows
  Mark pending / Mark booked / Return to draft / View issued; booked shows Mark completed); **Fuel
  price record** (selected week + "create corrected draft revision" select); issued/booked/completed
  date cards; **Revision history** table (revision, route, stored total, recorded, markers: Current
  working / Issued / Accepted); **Shared run builder** link; **Automatic route** summary (provider
  HERE, resolved-at); **Exception audit** table when present (type, original, replacement, reason,
  operator Cliff Edwards, recorded); **weekly fuel** + **rate setting** context cards; **Priced route
  legs** table (leg, start, end, miles used, rate type, rate/mile 6dp, amount); collapsible
  **Calculation explanation** (fuel context, rate inputs, resolved rates incl. horse-count multiplier,
  totals, per-leg calcs).
- Two totals must be visually distinct so it's clear final can differ from engine.
- States: draft (pre-issue) is the primary render; also worth a "quoted" variant (override badge +
  pipeline buttons).
- Exclude: payment, email send, maps.

---

## 6. Quote exceptions (route-leg + final-total override)

**Image prompt:**

> [STYLE PREAMBLE] Page title "Quote exceptions for revision 3" with caption "Original route and
> engine values stay visible. A change creates a new draft revision with a complete audit record." An
> "Adjust a disputed route leg" card: per-leg sub-panels each showing "Original route value: 90",
> Replacement miles, Reason category select (Postcode ambiguity / Disputed mileage / Operational
> exception), Explanation textarea. A "Final total override" card: "Engine total: £282.27", a Final
> total input, Reason category (Commercial adjustment / Customer agreement / Goodwill adjustment),
> Explanation, primary "Apply final total override". 16:9.

**Build spec:**
- Route `jobs.revisions.exceptions` (`job-revisions/exceptions.blade.php`).
- Route-leg override form (`route-leg-overrides`): per leg `overrides[n][miles]`,
  `overrides[n][reason_category]` (postcode_ambiguity / disputed_mileage / operational_exception),
  `overrides[n][explanation]`; shows original route value inline.
- Final-total override form (`final-total-override`): `final_total` (min 0.01), `reason_category`
  (commercial_adjustment / customer_agreement / goodwill_adjustment), `explanation`. Engine total
  stays displayed and retained.
- Tone: deliberate, auditable; originals always visible beside replacements.

---

## 7. Issued quote (printable document)

**Image prompt:**

> A clean **printable A4 quote document** (NOT the app chrome — no sidebar), white paper on a light
> grey backdrop with a soft shadow, a gold top rule. Header: small caps "SOUTH WEST EQUINE SERVICES",
> serif "Issued quote", and a right-aligned "Quote reference SWE-2026-0042 / Issued 3 Oct 2026". Two
> columns: Customer (Sarah Whitmore, email, telephone) and Journey (Pickup TA1 1AA, Drop-off EX4 3PL,
> Horses 2, Requested date, Constraints "None known"). A "Route basis" table (provider HERE; legs with
> journey, miles, rate type, amount). A "Pricing evidence" block with a highlighted pair of totals on
> soft gold panels: Engine total £282.27 and Final total £282.27. Footer buttons "Return to quote
> workspace" and "Print or save as PDF". Serif headings (Georgia), body Arial-like. 3:2.

**Build spec:**
- Route `jobs.revisions.issued` (`job-revisions/issued.blade.php`). **Standalone HTML document**, not
  the operator shell — its own print stylesheet (navy `#182437`, gold rule `#b78b2f`, gold total
  panels `#f4ead1`). This is a staff-held record for manual customer delivery, not an email.
- Sections: header + `issue_reference` + issued-at; Customer (name/email/phone); Journey
  (pickup/dropoff postcodes, horse_count, requested date or "To be arranged", constraints); Route
  basis table (leg, journey, miles, rate type, amount); Pricing evidence (fuel source, rate setting,
  Engine + Final totals); Route audit evidence (routing version, transport mode, provider status,
  per-leg entered/resolved); Exceptions table (or "No route or pricing exceptions"); actions (Return,
  Print). Actions hidden in print.
- Exclude: payment terms, bank details, e-signature.

---

## 8. Shared run builder

**Image prompt:**

> [STYLE PREAMBLE] Eyebrow "SHARED LOADS", serif title "Build a shared run", caption about linking
> draft quote revisions and splitting only genuinely shared miles. A "Parent shared run" card: Run
> name, Run date, Shared run notes. A "Customer allocation 1" card: a "Draft quote revision" select
> ("Revision 2: Emily Rogers (EX4 3PL to BS40 5QL)"), then a table with columns Leg / Stored route
> miles / Full miles / Split miles / Split divisor / Reasoning, three leg rows with number inputs and
> a reasoning textarea. A gold "£240.87" badge on the allocation header. Primary "Save shared run".
> 16:9.

**Build spec:**
- Routes `shared-runs.create` / `shared-runs.show` (`shared-runs/form.blade.php`).
- Parent fields: `name`, `run_date`, `notes`. When saved, three metric cards (Run date, Depot
  postcode derived, Customer allocations count).
- Each allocation: `allocations[i][job_revision_id]` select of available **draft** revisions; a
  per-leg table (`full_miles`, `split_miles`, `split_divisor` min 2, `reason`) for depot→pickup,
  pickup→drop-off, drop-off→depot; when priced, cards for Full charge miles / Split charge miles /
  Calculated total (£240.87).
- Empty state: "No draft transport quote revisions are ready…" + "Create a new transport quote".
- Concept: each customer still gets a full three-leg quote; only genuinely overlapping miles split.

---

## 9. Loading practice — new quote

**Image prompt:**

> [STYLE PREAMBLE] Eyebrow "LOADING PRACTICE" is separate from transport. Serif title "New
> loading-practice quote". A "Customer details" card: Customer name, Contact name, Email, Phone,
> Customer postcode, Customer notes. A "Loading-practice pricing" card: Travel miles, On-site hours,
> "Handling and loading livery period" select (None / Day / Week / Fortnight), Handling and loading
> livery quantity, Manual final total, Manual final total reason, Quote notes. Primary "Save
> loading-practice quote". 16:9.

**Build spec:**
- Route `loading-practice-quotes.create` (`loading-practice-quotes/_form.blade.php`).
- Customer fields: `customer_name`, `customer_contact_name`, `customer_email`, `customer_phone`,
  `customer_postcode`, `customer_notes`.
- Pricing fields: `travel_miles`, `on_site_hours`, `handling_livery_period` (none/day/week/fortnight),
  `handling_livery_quantity`, `manual_final_total`, `manual_final_total_reason`, `quote_notes`.
- Distance bands drive package price (within 15mi £30 / 25mi £45 / 50mi £90 / over 50mi = POA).
- Keep it visually distinct-but-consistent so it reads as a separate workflow from transport quoting.

---

## 10. Loading practice — quote detail

**Image prompt:**

> [STYLE PREAMBLE] Serif title with a customer name (Mr & Mrs Carter). Three metric cards: Status
> (Draft, distance band "within 50 miles"), Engine total £90.00, Final quoted total £90.00. The same
> edit form as the create screen, pre-filled. A "Rate setting used for this quote" card listing the
> loading-practice prices (within 15/25/50 miles, on-site hourly, livery day/week/fortnight). An open
> "Calculation explanation" panel: Package breakdown (travel miles, distance band, package price,
> on-site amount, handling livery) and Totals. Show a POA variant where over-50-mile travel makes
> totals read "POA". 16:9.

**Build spec:**
- Route `loading-practice-quotes.show` (`loading-practice-quotes/show.blade.php`).
- Metric cards: Status + `distance_band`; Engine total (or "POA" when `is_poa`); Final total.
- Embeds the same `_form` for editing; then a rate-setting card (loading-practice prices) and a
  Calculation-explanation panel (travel_miles, distance_band, package_price, on_site.amount,
  handling_livery.amount, engine/final totals).
- POA state: over-50-mile travel → package price and totals display "POA" until a manual amount.

---

## 11. Transport days — list

**Image prompt:**

> [STYLE PREAMBLE] Eyebrow "SCHEDULING", serif title "Transport days", caption about grouping jobs
> into a day's run in order. Header primary "New transport day". A "Recorded transport days" table:
> Date / Name / Jobs / Manage — rows like "14 Apr 2026 · Devon run · 3 · Open", "15 Apr 2026 ·
> Unnamed run · 2 · Open". A calm empty-state variant: "No transport days yet. Create one to start
> scheduling jobs into a run." 16:9.

**Build spec:**
- Route `transport-days.index` (`transport-days/index.blade.php`).
- Table: run_date, name (or "Unnamed run"), jobs_count, Open link. Header action "New transport day".
- Exclude: per-day miles/duration here (those live on the dashboard/day detail, not this list).

---

## 12. Transport day — create

**Image prompt:**

> [STYLE PREAMBLE] Eyebrow "SCHEDULING", serif title "New transport day", caption "Give the day a
> date, and optionally a name and depot, then add jobs to it." A single form card: Run date
> (required), Name (optional), Depot postcode (optional, TA1 1AA), Notes textarea. Primary "Create
> transport day" and an outline "Cancel". 16:9.

**Build spec:**
- Route `transport-days.create` (`transport-days/create.blade.php`).
- Fields: `run_date` (required), `name`, `depot_postcode`, `notes`. Actions: Create / Cancel.

---

## 13. Transport day — detail (schedule)

**Image prompt:**

> [STYLE PREAMBLE] Serif title "Devon run" with caption "Monday 14 April 2026 · depot TA1 1AA". A
> "Jobs in this day" card with a note "Listed in the order they will be done. Grouping a job here does
> not change its price." and a count badge "3". A table: Order / Status / Customer / Route / Actions —
> rows: 1 · pill Completed · Mr & Mrs Carter · TA6 3NT to TQ13 9AA · [Up][Down][Adjust][Remove];
> 2 · pill Booked · Sarah Whitmore · TA1 1AA to EX4 3PL; 3 · pill Pending · Emily Rogers · EX4 3PL to
> BS40 5QL. Below, an "Add a job to this day" card with a Job select and "Add to day". Optionally
> render depot start/finish markers framing the ordered stops. 16:9.

**Build spec:**
- Route `transport-days.show` (`transport-days/show.blade.php`). Reuses `route-stop` /
  `transport-day-card` visual language from the dashboard.
- Member jobs table: sequence (1..n), status pill, customer name, pickup→dropoff postcodes, actions
  (move up/down, Adjust → revision, Remove). Add-job form: `job_id` select of unassigned jobs.
- Depot start and finish both labelled **Depot** + postcode (never "Home").
- Exclude: stop times, driver/vehicle, optimise-route, combined-route map.

---

## 14. Admin — weekly fuel prices

**Image prompt:**

> [STYLE PREAMBLE] Eyebrow "FUEL ADMIN", serif title "Weekly fuel price control". A "Current active
> fuel input" card with an "Active" pill: Week commencing (29 Sep 2026, "Texaco — manual entry") and
> "Price per litre inc VAT £1.5300" with an activated timestamp. An "Add a weekly fuel entry" form:
> Week commencing, Source select, Price per litre inc VAT, an "Make this the active fuel input now"
> checkbox. A small "Add a fuel source" form. A "Weekly fuel history" table: Week / Source / Price /
> Status (Active or History) / Actions ("Set as active override"). 16:9.

**Build spec:**
- Route `admin.weekly-fuel-prices.index` (`admin/weekly-fuel-prices/index.blade.php`).
- Active card: `week_commencing`, source label, `price_per_litre_inc_vat` (4dp), activated_at.
- Add-entry form: `week_commencing`, `source` (select from fuel sources), `price_per_litre_inc_vat`,
  `activate_now`. Add-source form: `display_name`. History table with activate action per row.
- Emphasis: pricing stays visible/inspectable; the active record drives new quotes.

---

## 15. Admin — rate settings

**Image prompt:**

> [STYLE PREAMBLE] Eyebrow "RATE ADMIN", serif title "Rate settings control", caption about keeping
> visible transport assumptions out of code. A "Current active rate setting" card ("Active" pill):
> setting name + depot TA1 1AA, and a two-column definition list — Miles per gallon 22.0000, Litres
> per gallon 4.5400, Maintenance per mile 0.050000, Unloaded add on 0.555556, Loaded add on 0.806452,
> One horse multiplier 1.500000, Two horse multiplier 1.750000, Shared load percentage 75.00%; plus a
> "Loading practice prices" list (within 15/25/50 miles £30/£45/£90, on-site £25.00, livery
> day/week/fortnight £35/£220/£410). Below, an "Add a rate setting record" form (same fields) and a
> "Recorded rate settings" table (Name / Effective from / Depot / Status / Actions). Numbers must be
> crisp and aligned. 16:9.

**Build spec:**
- Route `admin.rate-settings.index` (`admin/rate-settings/index.blade.php`).
- Active card fields: name, depot_postcode, miles_per_gallon (4dp), litres_per_gallon (4dp),
  maintenance_per_mile (6dp), unloaded_add_on_per_mile (6dp), loaded_add_on_per_mile (6dp),
  one_horse_multiplier, two_horse_multiplier, shared_load_percentage (as %), and 7 loading-practice
  prices.
- Add form mirrors those fields + `name`, `depot_postcode`, `effective_from`, `effective_until`,
  `activate_now`. Recorded table: name, effective_from, depot, status (Active/Stored), activate action.
- This is a numbers-dense admin screen; prioritise legible tabular alignment over decoration.

---

## 16. Admin — operational evidence

**Image prompt:**

> [STYLE PREAMBLE] Eyebrow "ADMINISTRATION", serif title "Operational evidence", caption "Read-only
> route and quote evidence for operational review." A row of four stat tiles: Route attempts 24,
> Issued quote revisions 12, Enquiry to quote-ready "2m 40s", Enquiry to issue "3m 05s". A wide table:
> Route outcome (resolved: 20, operator_review_required: 3, unresolved: 1) / Attempts 24 / Failure
> categories / Exception reasons / Manual fallback rate / Route-leg override rate / Final-total
> override rate. Calm, analytical, read-only (no action buttons). 16:9.

**Build spec:**
- Route `admin.operational-evidence.index` (`admin/operational-evidence/index.blade.php`). Read-only.
- Four stat tiles: total attempts, issued quote revisions, quote-ready turnaround, issued turnaround.
- One evidence table: route-outcome status counts, total attempts, failure categories, exception
  reasons, manual-fallback rate, route-leg-override rate, final-total-override rate.
- Exclude: 7-day revenue trend, charts with fabricated series, any write action.

---

## Notes for the code-generation step

- Reuse existing Blade components where they exist rather than inventing new ones: `operator-shell`,
  `operator-sidebar`, `operator-topbar`, `dashboard-summary-card`, `transport-day-card`, `route-stop`,
  `status-badge` / `job-status`. Components take data via attributes and must not query models.
- Keep pricing logic out of views — it belongs in `app/Services/Pricing/*`. Views only display
  stored totals and evidence.
- The design system CSS already exists (`resources/css/dashboard-prototype.css`) — prefer extending
  its tokens over introducing new colours.
- Regenerate/adjust this file if the data model changes (new fields, new statuses, new pages).
