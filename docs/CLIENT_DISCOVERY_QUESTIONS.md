# Pre-Proposal Discovery — 20 Questions for the Client

**Purpose:** gather everything needed to write a scoped, priced, honest proposal for
the GHG emissions accounting platform — in one meeting.

**Format:** 60–90 minutes. Sections 1–3 in the first half, 4–5 in the second.
Bring a screen: a 10-minute walkthrough after Section 3 makes the later answers
much sharper.


**How to use this sheet**

- Ask the questions in order. The sequence moves from *why* to *what* to *how*.
- The **Why we ask** line is your prompt, not a line to read aloud. It says what
  the answer changes in the proposal.
- Write the answer down verbatim where you can. Paraphrasing loses the detail
  that later turns into a scope dispute.
- **Flag anything the client cannot answer.** An unanswered question is a project
  risk. We either scope a paid discovery phase for it or state a written
  assumption in the proposal — we never guess silently.

**Tags** show which part of the proposal each answer feeds:
`Positioning` · `Phasing` · `Scope` · `Compliance` · `Effort & price` · `Architecture`

---

## Section 1 — Why now, and who decides (Q1–Q3)

### Q1. How are you tracking emissions today — spreadsheets, a consultant, another platform — and what specifically is not working?

`Positioning`

**Why we ask:** tells us whether we are replacing a spreadsheet (fast win, light
integration) or displacing an incumbent system (feature-parity checklist plus a
data migration). It also hands us the exact pain to write the proposal against.

### Q2. What is driving this now — a regulation, an investor or customer request, a tender requirement, or an internal target? Is there a fixed date you must report by?

`Positioning` `Phasing`

**Why we ask:** a hard reporting deadline becomes the fixed point of the whole
plan and decides what must land in phase 1 versus what can follow. "No deadline"
is also a useful answer — it usually means a longer sales cycle.

### Q3. Who will use the system day to day, who signs off the final numbers, and who approves this purchase?

`Positioning` `Architecture`

**Why we ask:** separates the data-entry users from the approver and from the
buyer. Drives the role and permission design, and tells us who the proposal is
actually written for.

---

## Section 2 — What sits inside the inventory (Q4–Q7)

### Q4. Which legal entities must we cover — one company, or a group with subsidiaries and joint ventures? And do you consolidate by operational control, financial control, or equity share?

`Scope` `Architecture`

**Why we ask:** decides whether this is a single organisation or a group
structure with company switching and consolidated reporting. The consolidation
approach is a GHG Protocol choice that changes which entities count *at all*, so
it has to be settled before any data is entered.

### Q5. How many sites or facilities, and in which countries?

`Scope` `Effort & price`

**Why we ask:** the country mix determines which grid electricity factors and
national factor sets we load. Site count is the main driver of setup effort and
of licence sizing.

### Q6. Which reporting years do you need — just the current one, or historical years too? And have you chosen a base year to measure reductions against?

`Scope` `Effort & price`

**Why we ask:** back-years mean a data migration workstream, frequently the
largest single line in the proposal. A base year must exist before any target or
reduction claim can be made, and changing it later forces a recalculation.

### Q7. Do you need Scope 1, 2 and 3 from day one, or would you rather start with Scope 1 and 2 and add Scope 3 later?

`Scope` `Phasing`

**Why we ask:** Scope 3 is typically 70–90% of a footprint and by far the most
work — supplier data, 15 categories, estimation methods. Phasing it is the single
biggest lever we have on both timeline and cost.

---

## Section 3 — Compliance, assurance and claims (Q8–Q11)

### Q8. For Scope 3, do you already know which of the 15 categories are relevant to you — or do you need help working that out and justifying the ones you exclude?

`Scope` `Compliance`

**Why we ask:** most organisations do not know their own operational boundary.
The GHG Protocol requires all 15 categories to be screened with a written
justification for every exclusion, and an assurer will ask for it. That is a
scoped deliverable in its own right, not an assumption.

### Q9. Which frameworks or regulations must the output satisfy — CSRD/ESRS E1, CDP, GRI 305, ISSB/IFRS S2, EAD Abu Dhabi MRV, EU-ETS, or a customer's own template?

`Compliance` `Scope`

**Why we ask:** each framework is a distinct export with its own datapoints and
validation. Regulated MRV submissions (EAD, EU-ETS) go further still: they need
facility-level, tier-based data with uncertainty figures that ordinary GHG
accounting never collects. Getting this list wrong is the most expensive
misunderstanding available to us.

### Q10. Will the inventory be externally verified or assured? If so, by whom, to what level — limited or reasonable — and when?

`Compliance` `Architecture`

**Why we ask:** assurance changes the *product*, not just the reporting. It
requires supporting evidence attached to each record, a complete audit trail of
who changed what, and the ability to lock a reporting period once it is signed
off. All three are far cheaper to build in than to retrofit.

### Q11. Do you buy renewable energy — a green tariff, RECs/I-RECs, or a PPA? And do you have public reduction targets, or plan to set science-based ones?

`Compliance` `Scope`

**Why we ask:** any renewable-energy claim obliges you to report Scope 2 twice —
location-based and market-based — with the certificates tracked and matched to
consumption. Targets require baseline tracking and progress reporting against
them. Both are additional modules.

---

## Section 4 — Data and systems (Q12–Q16)

### Q12. Where does the underlying data live today — utility bills, fuel cards, meter readings, travel bookings, ERP or accounting records, spreadsheets? Roughly how many transactions a month?

`Effort & price` `Architecture`

**Why we ask:** volume decides whether manual entry is realistic at all or
whether import and integration are mandatory from day one. It is also a primary
input to licence sizing.

### Q13. What form does that data arrive in — PDF bills, Excel or CSV, a database, an API? Would you want documents read automatically rather than typed in?

`Effort & price`

**Why we ask:** Excel and CSV are a straightforward import. PDFs and scanned
bills need OCR and AI extraction plus a human review step — a separate module
with a real cost. Worth a frank cost-versus-hours conversation in the room.

### Q14. Which existing systems should this connect to — ERP or accounting (SAP, Oracle, Zoho, Dynamics), HR for headcount, fleet telematics, building management or meters? Should we pull from them, or will files be uploaded?

`Architecture` `Effort & price`

**Why we ask:** every integration is a discrete, separately priced work package,
and each one depends on *their* IT team's availability — the most common cause of
slippage on projects like this. Manual upload keeps phase 1 fast and cheap.

### Q15. Which emission factor sources must we use — DEFRA, EPA, IEA, national grid factors, supplier-specific? Who updates them each year, you or us? And where activity data genuinely isn't available, is spend-based estimation acceptable — in which currencies?

`Scope` `Compliance`

**Why we ask:** factors have to be versioned by year and traceable to a published
source, or an auditor will challenge the numbers. Annual factor maintenance is a
recurring service line, not a one-off build. Spend-based estimation needs its own
factor set and currency handling.

### Q16. Do you need to collect primary data from your suppliers — and roughly how many suppliers would you survey?

`Scope` `Effort & price`

**Why we ask:** supplier engagement is a workflow of its own — survey templates,
an external portal for suppliers to respond through, reminder chasing, then
converting replies into Scope 3 figures. Supplier count drives the effort
directly.

---

## Section 5 — Platform, governance and commercials (Q17–Q20)

### Q17. How many users? And do you need a review-and-approve step before a figure counts as final, with a full history of who changed what?

`Architecture`

**Why we ask:** a maker–checker workflow and an audit trail are standard for
audited carbon data, but the user count and the number of approval stages drive
both licensing and configuration effort.

### Q18. We can use AI to classify activities into the right scope, flag suspicious numbers, read documents, and answer plain-language questions about your data. Is that welcome — and does your policy allow company data to be processed by a third-party AI provider?

`Architecture` `Compliance`

**Why we ask:** some assurance-bound, government and public-sector clients
prohibit it outright. The platform is fully functional without AI, so this is a
per-client switch — but we need the answer *before* we design the data-entry
experience around it.

### Q19. Where should this run — our hosted cloud, your cloud tenancy, or on-premise? Any data-residency requirement, such as UAE or EU? And should staff sign in with your existing Microsoft or Google accounts?

`Architecture`

**Why we ask:** deployment model and residency change hosting cost, the security
review we have to pass, and the delivery timeline. Single sign-on is usually
expected by enterprise IT — quick to arrange if raised now, awkward if it surfaces
during rollout.

### Q20. What budget range is this sitting in, do you prefer a one-off build or an annual subscription, and what would make you call this a success twelve months from now?

`Positioning` `Effort & price`

**Why we ask:** the commercial model determines what we can responsibly propose.
The success answer tells us which capability to lead the proposal with — and it is
the measure they will actually judge us on, so it is worth getting in their own
words.

---

## If the meeting has room — five more

- **B1.** Should the platform carry your branding and domain? Any language beyond
  English — Arabic?
- **B2.** Who consumes the output, and in what form: a live dashboard, a
  board-ready PDF, Excel for the finance team, scheduled email summaries?
- **B3.** How many people need training, and what support level do you expect —
  business hours, or a formal SLA?
- **B4.** Is there historical data we would need to migrate, and what condition is
  it in?
- **B5.** How do you expect users, sites and entities to grow over the next three
  years?

---

## After the meeting — what we send

1. A written scope summary, including every assumption we had to make where an
   answer was missing.
2. A phased plan with the Q2 deadline as its fixed point.
3. A price built on Q5 (sites), Q6 (years and migration), Q7 (scopes),
   Q12 (volume), Q14 (integrations) and Q16 (suppliers).
4. An explicit **out of scope** list. Everything the client mentioned that we are
   *not* proposing goes here in writing — this is what prevents the argument in
   month three.
