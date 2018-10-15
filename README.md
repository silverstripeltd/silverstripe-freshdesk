# silverstripe-freshdesk

SilverStripe module which allows CMS admins to configure userforms to raise tickets with Freshdesk.

## Configuration

Define in `_ss_environment.php`:

* `FRESHDESK_HMAC_SECRET` - Optional, required for SSO.
* `FRESHDESK_TOKEN` - API token for an admin account on the Freshdesk. Used for API requests.
* `FRESHDESK_DOMAIN` - Account domain of freshdesk without the ".freshdesk.com" suffix. e.g. for "silverstripe.freshdesk.com" this would be "silverstripe". Used for SSO and API requests.
* `FRESHDESK_PORTAL_HOSTNAME` - Optional, if logging users into a different portal, use this to specify the Freshdesk portal URL. e.g. "silverstripe.freshdesk.com". If not set, defaults to "`FRESHDESK_DOMAIN`.freshdesk.com".
* `FRESHDESK_PRODUCT_ID` - Used to log and display tickets from a specific product in your portal.

Create a template for `FreshdeskPage` if you want a place for users to view their tickets.

## Usage

Adds two additional fields under `Configuration` on all `UserDefinedForms`:
* `Export as a Freshdesk ticket on submit` - Set to true to raise the submitted form as a ticket in Freshdesk
* `Freshdesk ticket description` - Purely cosmetic, you may want to have some sort of landing page for tickets where you show the types and a brief description.

Adds two additional `Main` options for all fields in a user defined form which allow the mapping a user defined form field to a specific Freshdesk field. This will ensure that the content is removed from the description block (main field of a ticket), in to the appripriate field in Freshdesk. If the field refers to a Freshdesk custom field, ensure you check the `Freshdesk custom field` box.

If `Export as a Freshdesk ticket on submit` is set to true, the user is logged in then the form will create a ticket in Freshdesk on submit.

## SSO configuration

Currently only the simple sso feature has been enabled. [See Freshdesk documentation for details](https://support.freshdesk.com/support/solutions/articles/31166-single-sign-on-remote-authentication-in-freshdesk)
To use simple sso, you will need to ensure `FRESHDESK_HMAC_SECRET` and `FRESHDESK_DOMAIN` are defined.
To log users into a different portal, use the `FRESHDESK_PORTAL_HOSTNAME` setting as described in the previous "Configuration" section.

### Routing from multiple portals.

The module allows you to route different portal URLs to different URLs. If you wish to route multiple portals based on the `host_url` send from the portal, ensure this module is also included on external sites - or implement your own solution.

To set up routes to different sites, edit eg. `mysite/_config/config.yml` and add the route:
```FreshdeskSSO:
  freshdeskPortalRedirects:
    'myproduct.example.com': 'http://www.neatproduct.com/'
```
