# JSON:API Views

> Creates [JSON:API Resource](https://www.drupal.org/project/jsonapi_resources) for [Views](https://www.drupal.org/docs/8/core/modules/views).

Entities resulting from a view query will be available at `/jsonapi/views/{view_id}/{display}`.

This endpoint should respect any exposed filter parameters configured for a display.


## Why would you ever want to do that?

This is useful in cases where you have multiple displays of a single view that share the same exposed filters, some of which are decoupled. This will allow the decoupled portions to limit their concerns to just displaying the resulting resources and gives site admins some flexibility in configuring their exposed filters.
