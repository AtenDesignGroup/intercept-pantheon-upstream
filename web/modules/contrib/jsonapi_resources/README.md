# JSON:API Resources

This module lets you define custom resources at routes of your choice that use 
existing resource types. This is useful for defining routes that return resource
 objects based on context like the currently authenticated user instead of a
route parameter (e.g. a UUID)

## Entity route parameters: UUID or ID, automatically

Any `entity:*` route parameter on a route that declares
`_jsonapi_resource` is upcast by **either** the entity's UUID **or** its
integer ID — whichever the URL contains. No `converter:` line needed:

```yaml
example.author_content:
  path: '/%jsonapi%/user/{user}/content'
  defaults:
    _jsonapi_resource: Drupal\example\Resource\AuthorArticles
    _jsonapi_resource_types: ['node--article']
  requirements:
    _permission: 'access content'
  options:
    parameters:
      user:
        type: entity:user
```

Both URLs resolve the same user entity:

- `/jsonapi/user/4b2c1e30-1234-4abc-89de-0123456789ab/content` (UUID — JSON:API convention)
- `/jsonapi/user/42/content` (integer ID — legacy)

The dispatch is value-based: UUIDs match a strict hex pattern that
numeric entity IDs cannot collide with.

### Forcing one or the other

Declare an explicit converter on the parameter to opt out of the dual
behavior:

```yaml
options:
  parameters:
    user:
      type: entity:user
      converter: 'paramconverter.jsonapi.entity_uuid'   # UUID-only
      # or:
      # converter: 'paramconverter.entity'              # integer-ID only
```
