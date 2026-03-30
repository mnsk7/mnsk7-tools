# Definition of Done (DoD)

## Applies to every change

- The change stays inside the allowed code zones.
- The requested outcome is actually implemented.
- Scope did not expand without reason.
- Runtime, deploy, or workflow contract changes are documented.
- No obvious PHP or JS breakage was introduced.
- Woo conversion guards still hold for the affected area.

## Low-risk change

A low-risk task is done when:

- the diff is minimal and reviewable
- the relevant page or artifact was checked
- lightweight verification appropriate to the risk was completed
- staging deploy is used if the change needs server confirmation

## High-risk change

A high-risk task is done when:

- the diff is minimal and reviewable
- pre-push review explicitly checks Woo conversion risk
- the change is pushed to `main`
- staging deploy completes
- targeted post-deploy verification is completed for the affected risk area

## Woo-specific completion checks

For tasks that touch Woo behavior, completion requires confidence that:

- add to cart works from PLP or PDP when affected
- cart remains accessible and usable when affected
- checkout entry still opens and shows the form when affected

## Staging safety

A deployable task is not done if staging safety is compromised:

- `blog_public` must remain disabled on staging
- customer mail must not go out from staging
- live payments must not be enabled on staging
