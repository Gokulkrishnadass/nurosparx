# Exercise 1 – Case Results CPT

## Approach

- Created a `case_result` CPT with support for title, content, thumbnail, and archive.
- Custom fields are implemented via a metabox (no ACF), storing:
  - Case Type (whitelisted options)
  - Settlement Amount (normalized numeric value)
  - Case Duration (months)
  - Client Location (free text)
  - Case Year (integer)

## Marketing + Analytics

- High-value cases (> $100,000) are highlighted using a custom query and grid layout.
- Schema.org `LegalCase` microdata is added for each card so marketing teams can use richer snippets.
- GTM `case_result_view` event is fired on click, including `case_type` and `settlement_amount` for funnel and ROI tracking.

## AJAX Filter

- Archive page has a Case Type dropdown.
- AJAX endpoint `nxs_filter_case_results` returns filtered cards without page reload.
- Uses nonces and sanitized input for security.

## Security

- Nonce checks for meta saving and AJAX.
- Sanitization for all meta fields.
- Whitelisting for case type values.
