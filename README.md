# silverstripe-freshdesk

SilverStripe module which allows CMS admins to configure userforms to raise tickets with freshdesk.

## Configuration

Define in `_ss_environment.php`:
* `FRESHDESK_API_TOKEN` - API token for an admin account on the Freshdesk.
* `FRESHDESK_PASSWORD` - Password for the same account.
* `FRESHDESK_HMAC_SECRET` - Optional, required for SSO.
* `FRESHDESK_PORTAL_BASEURL` - Optiona, required for SSO Base URL of your freshdesk portal (no protocol).
* `FRESHDESK_API_BASEURL` - Base URL of your freshdesk account (no protocol).

Create a template for `FreshdeskPage` if you want a place for users to view their tickets.

## Usage

Adds two additional fields under `Configuration` on all `UserDefinedForms`:
* `Export as a Freshdesk ticket on submit` - Set to true to raise the submitted form as a ticket in Freshdesk
* `Freshdesk ticket description` - Purely cosmetic, you may want to have some sort of landing page for tickets where you show the types and a brief description.

If `Export as a Freshdesk ticket on submit` is set to true, the `FRESHDESK_API_BASEURL` is defined and the user is logged in then the form will create a ticket in Freshdesk on submit.

## SSO configuration

// John TODO
