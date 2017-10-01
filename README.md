# silverstripe-freshdesk

SilverStripe module which allows CMS admins to configure userforms to raise tickets with freshdesk.

## Configuration

Define in `_ss_environment.php`:
* `FRESHDESK_API_TOKEN` - API token for an admin account on the Freshdesk.
* `FRESHDESK_PASSWORD` - Password for the same account.
* `FRESHDESK_HMAC_SECRET` //TODO
* `FRESHDESK_PORTAL_BASEURL` //TODO
* `FRESHDESK_API_BASEURL` - Base URL of your freshdesk account (no protcol)

## Usage

Adds two additional fields under `Configuration` on all `Userdefinedforms`, if `Export as a Freshdesk ticket on submit` is set to true and the `FRESHDESK_API_BASEURL` is defined then the form will create a ticket in Freshdesk on submit.
