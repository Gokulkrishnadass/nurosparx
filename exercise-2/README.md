# Exercise 2 – HubSpot Lead Integration

## Approach

- Implemented a REST endpoint `/wp-json/nxs/v1/lead` to receive POSTed form data.
- Validates:
  - Required name and email
  - Email format
  - Optional US phone in E.164 format
  - Honeypot field for anti-spam
- Rate-limits form submissions to 3 per IP per hour using transients.

## HubSpot

- Uses HubSpot Forms API endpoint with placeholder portal ID and form GUID.
- UTM parameters are mapped to custom properties (`utm_source`, `utm_medium`, `utm_campaign`).
- All fields are included in the `fields` array as HubSpot properties.

## Error Handling & Backup

- If HubSpot fails (HTTP error or non-2xx response), we:
  - Log a generic error message (no PII).
  - Still insert lead into `wp_nxs_lead_backups` table with status `failed`.
- On success:
  - Store backup with status `success`.
  - Return JSON `{ success: true, lead_id: <id> }`.

## Security

- Inputs sanitized with WordPress helpers.
- No API keys logged.
- SQL injection protection via `$wpdb->insert` and prepared values.
