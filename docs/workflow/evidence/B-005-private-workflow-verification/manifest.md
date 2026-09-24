# B-005 private workflow verification evidence

Status: implementation-free technical evidence, updated 2026-09-19.
Batch: [B-005-private-workflow-verification](../../current-batch.md#batch-scope), scope revision `r2`.

This manifest records synthetic browser walkthroughs against the approved working-tree candidate.
It records observed defects without changing application behaviour.

## Candidate and environment

| Evidence | Observed value |
| --- | --- |
| Repository HEAD | `c380baa0a95e9a2e81ff21182c0b492ce53f400a` |
| Working tree before evidence records | 34 modified tracked files and 140 untracked files |
| Working tree after evidence records | 34 modified tracked files and 145 untracked files |
| Full final status | [repository-status.txt](repository-status.txt) |
| Owned implementation fingerprint | 31 paths, SHA-256 `4b27806ce58815dd7c6756909f8f484a391d89254fd555539d5bf30a147e58a2` |
| Original protected baseline fingerprint | 199 paths, SHA-256 `0502d6ba8c23e4aaa75cda5acd22f52adbb5e92d242750b1c71345134c617a95` |
| Final protected fingerprint | 198 paths, SHA-256 `0de4b697522de2146d6f072e9cb6dff15d8360432b37edcd5943ce4760b58c87` |
| Disposable copy | `C:\Users\charl\AppData\Local\Temp\sweq-b005-c4c752f3` |
| Disposable database | SQLite, with synthetic records only |
| Browser | Codex in-app browser, Chromium 152 |
| Browser viewport | 1280 by 720 CSS pixels, device pixel ratio 1 |
| PHP | 8.5.3 |
| Composer | 2.9.5 |
| Node.js | 22.13.1 |
| npm | 10.9.2 |

The disposable copy excluded `.git`, `.env`, `node_modules`, frontend build output, and runtime data.
It included the existing Composer `vendor` tree.
The copied source contained 9,385 files and 69,840,671 bytes before npm installation and runtime setup.

Generated repository evidence comprises these exact files:

- `manifest.md`.
- `repository-status.txt`.
- `frontend-build.txt`.
- `host-verification.txt`.
- `screenshots/README.md`.

The refreshed derived records and workflow index are documentation changes, not runtime evidence.

## Approval identity verification

Identity verification ran on 17 September 2026.
It produced these expected and observed SHA-256 values:

| Identity | Expected | Observed |
| --- | --- | --- |
| Canonical intent `r1` | `fc253f7246c1d90da1417618ceb8d4a1a1590d04b94b6f59e597f9d6e51b5ce8` | `fc253f7246c1d90da1417618ceb8d4a1a1590d04b94b6f59e597f9d6e51b5ce8` |
| Pricing policy `r2` | `bb775278bc0d6ebdc1a6015b539e3694d66651088e782b3eab1648a17197a230` | `bb775278bc0d6ebdc1a6015b539e3694d66651088e782b3eab1648a17197a230` |
| Accepted B-004 scope `r1` | `4e9b930453776b1e14f085d3fd76ff1a21e6817ac205b628383547196c1f87c9` | `4e9b930453776b1e14f085d3fd76ff1a21e6817ac205b628383547196c1f87c9` |
| Accepted B-004 outcome `r1` | `00de60879337bd95d8912188aaea62ee7c7f9d87dc78df32d6eafbb78cafafe1` | `00de60879337bd95d8912188aaea62ee7c7f9d87dc78df32d6eafbb78cafafe1` |
| Approved B-005 scope `r1` | `2a9569e7f88634817c2948200540dffca58063bb33c17dde3e1ccf37f1014495` | `2a9569e7f88634817c2948200540dffca58063bb33c17dde3e1ccf37f1014495` |
| Approved B-005 scope `r2` | `12e64d6413fc4655eac32e8c0a75423e4f55d43bb0d18af06f822e5f913fc869` | `12e64d6413fc4655eac32e8c0a75423e4f55d43bb0d18af06f822e5f913fc869` |

Canonical and batch scopes use UTF-8, LF line endings, one final newline, and no trailing blank lines.
Each digest covers its named level-two heading and nested content.

B-004 verification first reversed the recorded archive link substitutions.
Its outcome digest spans Implementation Outcome through Verification Limits, before Acceptance Record.

## Final fingerprint verification

Final fingerprint verification ran on 19 September 2026 after scope revision `r2` approval.

The 31 non-workflow implementation paths retain aggregate `4b27806ce58815dd7c6756909f8f484a391d89254fd555539d5bf30a147e58a2`.
The remaining 198 protected paths retain aggregate `0de4b697522de2146d6f072e9cb6dff15d8360432b37edcd5943ce4760b58c87`.

The original 199-path protected set reconstructs to aggregate `0502d6ba8c23e4aaa75cda5acd22f52adbb5e92d242750b1c71345134c617a95` from the accepted snapshot.
Only `docs/workflow/derived/build-and-release-plan.md` left that set under revision `r2`.

The aggregate hashes each sorted UTF-8 path, a null byte, and its Git-clean blob identity.
No application, test, configuration, schema, or package-manifest path changed during B-005.

## Trial decisions

On 19 September 2026, the user named Cliff and Sophie as later trial operators for `southwest equine`.
No operator account, access, or trial activity is authorised by that decision.

The batch record contains a workflow scope derived from existing release and delivery documents.
The user approved that scope on 19 September 2026, including shared loads and separate loading-practice coverage.

The user deferred the minimum visual-quality gate until later.
That missing gate and the Important browser findings continue to block an operator-trial batch.

Workflow approval wording:

> approve

## Routing fixture

All route responses came from a loopback-only PHP fixture on `127.0.0.1:8766`.
No live HERE request or credential check occurred.

Fixture SHA-256: `32748afe8738f60e7172a3a51877d45e71c7146c55470e3a3840135de6972d14`.

The geocoder returned each supplied postcode with latitude `50.9020` and longitude `-3.4900`.
The route response used identifier `b005-synthetic-route`.
Its section lengths were 16,093, 144,841, and 154,497 metres.
The application displayed 10, 90, and 96 quoted miles.

## Screenshot evidence limitation

The browser tool emitted reviewed screenshots inline during W1 through W9.
It exposed no supported path for retaining those captures as repository files.

The [screenshot index](screenshots/README.md) records each reviewed inline capture.
No image file is represented as durable repository evidence.
Acceptance criterion A14 therefore remains unsatisfied.

## W1 standard one-horse quote

Inputs used `Synthetic One Horse`, `one@example.invalid`, one horse, `EX1 1AA`, and `PL1 1AA`.
The requested date was 1 October 2026.

Actions entered the enquiry, resolved the route, reviewed three legs, and accepted the route for pricing.
The expected P1 engine and final totals were both `255.90`.

The browser showed 10, 90, and 96 miles.
Revision 1 stored engine and final totals of `255.90`.
The leg amounts were `9.21`, `158.25`, and `88.44`.

Supporting records are enquiry 1, route resolution 1, job 1, and revision 1.
Result: pass.

## W2 standard two-horse quote

Inputs used `Synthetic Two Horse`, `two@example.invalid`, two horses, and the W1 route.
Actions repeated enquiry entry, route review, and route acceptance.

The expected P2 engine and final totals were both `282.27`.
Job revision ID 2, revision number 1, stored both totals as `282.27`.

Supporting records are enquiry 2, route resolution 2, job 2, and job revision ID 2.
Result: pass.

## W3 shared one-horse allocation

Two fresh one-horse draft quotes used `Synthetic Shared A` and `Synthetic Shared B`.
Both used the W1 route and synthetic reserved-domain addresses.

Actions created both route-backed drafts, opened the shared-run builder, selected both revisions, and entered each allocation leg.
The walkthrough saved the parent run and reviewed both repriced customer allocations.

Allocation one used full and split miles of `6/4`, `60/30`, and `96/0`.
The two shared legs used divisor 2.

The expected P6 totals were `240.87` for allocation one.
The browser showed 162 full miles, 34 split miles, and total `240.87`.

Allocation two showed 129 full miles, 34 split miles, and total `197.91`.
Its values supplied the second valid participant in the shared run.

Supporting records are shared run 1, allocations 1 and 2, jobs 5 and 6, and revisions 8 and 9.
Result: pass for P6 allocation one.

## W4 unsupported horse count

Inputs used `Synthetic Unsupported Count`, `three@example.invalid`, three horses, and the W1 route.
Actions saved the enquiry, reviewed the route, and attempted automatic pricing.

The expected result preserved the enquiry, created no priced job, and displayed manual-review handling.
The database preserved enquiry 3 with `quote_job_id` null.
No new job, revision, route leg, or exception audit appeared.

The browser returned to route review without a manual-review error message.
The pricing button remained available.

Supporting records are enquiry 3 and route resolution 3.
Result: fail for visible manual-review handling.
Finding F1 records this application defect.

## W5 corrected-fuel revision selection

The active fuel record remained 10 August 2026 at `1.5300`.
An inactive correction used 17 August 2026 at `1.6000`.

Actions selected the correction from job 1 revision 1 and created a new draft revision.
The expected P7 total was `259.39`.

Revision 3 stored engine and final totals of `259.39`.
Revision 1 retained `255.90`, and fuel record 1 remained active.
Fuel record 2 remained inactive.

The issue checklist marked active fuel and rate context as needing attention.
It disabled the issue action for the corrected draft.

Supporting records are job 1, revisions 1 and 3, and fuel records 1 and 2.
Result: pass for correction and history preservation.
Finding F2 records the issue-workflow defect.

## W6 route-backed controlled override

Inputs used `Synthetic Override Route` and a fresh P1 draft.
The controlled exception form replaced final total `255.90` with `250.00`.

Actions opened the route-backed exception form, entered the replacement total, selected the category, added the explanation, and submitted the override.
The expected result preserved revision 4 and created revision 5 with a complete audit.

The reason category was `customer_agreement`.
The explanation stated that the synthetic agreement reduced the total by `5.90`.

Revision 5 retained engine total `255.90` and final total `250.00`.
Audit 1 retained actor `SWES Operations` and recorded time `2026-09-14 09:29:15`.
It also retained both totals, category `customer_agreement`, and the full explanation.
Revision 4 retained the original `255.90` totals.

Supporting records are job 3, revisions 4 and 5, and audit 1.
Result: pass.

## W7 legacy workspace controlled override

A synthetic pre-seeded legacy revision had no route resolution.
Its three manual route legs used 10, 90, and 96 miles.

Actions entered the replacement total, category, and explanation through the legacy workspace fields.
The expected result preserved revision 6 and created revision 7 with controls equivalent to W6.

The legacy workspace replaced final total `255.90` with `250.00`.
It used the same category and explanation as W6.

Revision 7 retained engine total `255.90` and final total `250.00`.
Audit 2 retained actor `SWES Operations` and recorded time `2026-09-14 09:33:02`.
It also retained both totals, category `customer_agreement`, and the full explanation.
Revision 6 retained the original `255.90` totals.

Supporting records are job 4, revisions 6 and 7, and audit 2.
Result: pass.

## W8 issued quote output

Actions confirmed revision 5 and issued the route-backed override quote.
The expected output retained its route, pricing, override, and revision evidence.

The issued page showed reference `SWEQ-3-R2`.
It showed the synthetic customer, three route legs, engine total `255.90`, and final total `250.00`.
It also showed route identifier `b005-synthetic-route`.

The exception row displayed category `customer_agreement` and the full explanation.
It displayed original total `255.90` and replacement total `250.00`.
It did not display the audit actor or recorded time.
Audit 1 retains actor `SWES Operations` and timestamp `2026-09-14 09:29:15` outside the issued output.

The desktop output was readable at the recorded viewport.
This technical observation is not operator acceptance or a wider visual-quality assessment.

Supporting records are job 3, revision 5, route resolution 4, and audit 1.
Result: pass.

## W9 transport-day assignment and ordering

Inputs used 1 October 2026, `Synthetic Verification Day`, depot `EX16 0AA`, and jobs 5 and 6.
Actions created `Synthetic Verification Day` for 1 October 2026.
They assigned jobs 6 and 5, then moved job 5 above job 6.

The expected result showed creation, assignment, ordering, and removal without changing either quote total.

After explicit confirmation, the walkthrough removed job 6 on 17 September 2026.
The final day retained job 5 at sequence 1.

Job 5 retained engine and final totals of `240.87`.
Job 6 retained engine and final totals of `197.91` after removal.

Supporting records are transport day 1 and jobs 5 and 6.
Result: pass.

## Frontend production build

The temporary installation used the existing package ranges and created no repository lockfile.
The temporary lockfile SHA-256 was `24569429c04a346f56c782b3414f95504958ae72ada016d29d22aac05804f7bc`.

Resolved top-level versions were:

- `@tailwindcss/vite` 4.3.3.
- `concurrently` 9.2.4.
- `laravel-vite-plugin` 3.2.0.
- `tailwindcss` 4.3.3.
- `vite` 8.3.0.

`npm run build` exited 0 on 17 September 2026.
Vite transformed three modules and completed in 820 milliseconds.
The retained [build transcript](frontend-build.txt) records the command output.

The build warned that optional package `fontaine` was absent.
No dependency was added because B-005 excludes package changes.

| Output | Bytes | SHA-256 |
| --- | ---: | --- |
| `public/build/manifest.json` | 1,478 | `3c1caac5610956cc0b8fef5b014fca177c246bb52bd4b1dc0a9374b8ad190f70` |
| `public/build/fonts-manifest.json` | 5,742 | `66edf17c93351fe01158be7414a441a762f106417c74351881876b405b8b10ca` |
| `public/build/assets/app-CO32mvgn.css` | 56,826 | `7bb0d5d071bb340c89a3327cff0f8303a83408f3177ac960fa327ce59e8256a5` |
| `public/build/assets/fonts-C9MNnjVw.css` | 2,352 | `3846a9bb60bfad92960eadcccaf0b4e129d74b11d48791506c8d1a827a385cc8` |
| `public/build/assets/app-BvRk9kiK.js` | 0 | `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855` |
| `public/build/assets/instrument-sans-400-normal-D1W7dsQl.woff` | 21,240 | `797bd3eaa70edbd4757650ab44dad75fbfbfdf18bacedb4b71022c04621d30ad` |
| `public/build/assets/instrument-sans-400-normal-DRC__1Mx.woff2` | 16,860 | `9a91efaa3599a6ef01b2f49e9ec670e3a2f39baeef09c73198f1052cf3bb0850` |
| `public/build/assets/instrument-sans-500-normal-Dk9ku72i.woff2` | 17,232 | `9d3547da16d950d12a677c772a3ff9b69846e60e7dcd1b5efd540993f7286532` |
| `public/build/assets/instrument-sans-500-normal-Z6ESRlEs.woff` | 21,652 | `bd6a8dc8b0dc0cced0c32d3eda93f0e0e82c58ddefea8b53b3307b0c48da3089` |
| `public/build/assets/instrument-sans-600-normal-B7fBEWYG.woff2` | 17,408 | `efb770916eda95a0e31d80cccde9651d4e8617010b21220e9f227c4820d675da` |
| `public/build/assets/instrument-sans-600-normal-B9e8oLYv.woff` | 21,676 | `9b4ce1f2b26be7cb087fd87d6f99a202136292ec820af75bd38ab9e53895cf28` |

All build outputs remain temporary and outside the repository.

## Host verification

`composer run test:host` exited 0 on 17 September 2026.
It passed all 203 tests with 1,417 assertions in 19,699 milliseconds.

`composer validate --no-check-publish` exited 0.
It reported that `composer.json` is valid.

The retained [host transcript](host-verification.txt) records both command results.

Both commands emitted the known duplicate-extension warnings.
The warnings did not change either exit code.

## Findings

### F1 Important: unsupported counts lack visible manual-review handling

The application rejects automatic pricing and preserves the enquiry correctly.
The route-review page displays no error, explanation, or manual-review instruction after rejection.

This leaves A8 unsatisfied and can mislead an operator into repeating the action.
No fix was attempted.

### F2 Important: inactive corrected fuel blocks quote issue

The application creates the corrected draft and preserves the active fuel record correctly.
The issue checklist then rejects that intentionally selected inactive correction as stale context.

This blocks issue of the corrected-fuel draft without activating the correction.
No fix was attempted.

### F3 Important: screenshots are not durable repository evidence

Every final browser state was visually reviewed through an inline tool capture.
The tool provided no supported repository export path for those images.

This leaves A14 unsatisfied despite the structured browser and database evidence.
No substitute image or fabricated file was created.

### F4 Minor: optional font fallback optimisation is unavailable

The production build succeeds without `fontaine`.
Vite reports that optimised font fallbacks are unavailable.

No dependency was added.

## Privacy and scope check

All entered identities used synthetic names and reserved `.invalid` addresses.
The retained documentation contains no password, application key, HERE credential, or personal customer record.

No PostgreSQL, live HERE, deployment, release, configuration activation, operator trial, pricing-policy change, or visual redesign occurred.
