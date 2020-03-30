CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended Modules
 * Development
 * Installation
 * Events
 * API Methods Included in Intercept
 * Intercept Event and Room Field Names
 * Finding UUIDs for Use in Queries
 * RSS Feeds for Digital Signage
 * Maintainers


INTRODUCTION
------------

Intercept is an event management system designed to help libraries and other
organizations evolve and track their event programming.

This suite of modules includes:

* Event Management
* Room Reservations
* Equipment Reservation
* Customer Attendance Tracking & Feedback

For a full description of the module, visit the project page:<br>
https://www.drupal.org/project/intercept

To submit bug reports and feature suggestions, or track changes:<br>
https://www.drupal.org/project/issues/intercept


REQUIREMENTS
------------

Intercept Core requires the following contributed modules:

* Address (https://www.drupal.org/project/address)
* Consumers (https://www.drupal.org/project/consumers)
* Consumer Image Styles (https://www.drupal.org/project/consumer_image_styles)
* Entity (https://www.drupal.org/project/entity)
* Field Group (https://www.drupal.org/project/field_group)
* Focal Point (https://www.drupal.org/project/focal_point)
* Profile (https://www.drupal.org/project/profile)

Some of the included modules have their own dependencies which should be
installed automatically as you enable them.


RECOMMENDED MODULES
-------------------

  * Intercept Base Theme (https://www.drupal.org/project/intercept_base):<br>
  This theme is custom-built to be the customer-facing theme.
  * Material Admin (https://www.drupal.org/project/material_admin):<br>
  This theme is recommended as the current administrative theme.
  * Polaris (https://www.drupal.org/project/polaris):<br>
  This plugin module can be used by libraries that utilize the Polaris ILS.


DEVELOPMENT
-----------

### JavaScript
This module and its submodules consist of many React applications that share a dependency on a common interceptClient Library.
The React applications are built using the themeable [Material-UI](https://material-ui.com/) suite of components.

It is highly recommended that you install the [React Dev Tools](https://chrome.google.com/webstore/detail/react-developer-tools/fmkadmapgofadopljbjfkapdkoienihi?hl=en) and [Redux Dev Tools](https://chrome.google.com/webstore/detail/redux-devtools/lmhkpmbekcpmknklioeibfkpmmfibljd?hl=en) extensions for Chrome.

#### interceptClient
interceptClient provides a shared Redux store, Reselect selectors and Drupal JsonAPI integrations to help fetching and manipulating Drupal content as well as many other utility functions that help making interacting with Intercept a little easier.

INSTALLATION
------------
`npm install`

### Development Build
To run the development build which will bundle the apps with the development version of React and include source maps to aid in debugging, run the following.
`npm run watch:js`

### Production Build
The production build will use an external production build of React hosted on a CDN, and minify the code.
`npm run build:js`

When the build is complete, a visual representation of the Webpack bundle will load in your browser. Until this is made optional, you may just type `Ctrl+c` to complete the build task.

*The production build should be committed to the repository. Currently this is a manual process.*


CONFIGURATION
-------------

See https://www.libraryintercept.com/how/


EVENTS
------

### Event status logic
* default status: open
* check if there is no event date, if true return default status
* check if event has expired, if true return 'expired'
* check if reg is not required, or if there is no reg date, return default status
* check if reg end date has passed, if so return 'closed'
* check if reg is in process and if the capacity is full
  * if there is a waiting list, check if that is full
    * if it's full return 'full'
    * if it's not return 'waitlist'
* check if reg start date has not happened, if true return 'open_pending'
* return default status


API METHODS INCLUDED IN INTERCEPT
---------------------------------

If you're planning to use Intercept to drive data to another source such as
digital signage, you may want to utilize the API endpoints/methods that are
included as part of Intercept. Although all of the endpoints are documented
below, only a few are publicly accessible. The rest are currently only available
for use within the application itself.

Since Drupal core now includes JSON API for queries related to content, queries
for information about events, rooms, and locations (all of which are standard 
Drupal content "nodes") can all be built using the JSON API standard syntax.
Example queries and responses are included below. Also included below are
instructions on how to get a full list of Intercept fields that can be used in
your own JSON API queries and instructions on how to find the UUID values of
your rooms and locations for use in queries.

* Intercept Core
  * /api/customer/search
    * Permissions: Required: search customer
    * Internal-only API method
  * /api/customer/register
    * Methods: POST
    * Permissions Required: search customer
    * Internal-only API method
* Intercept Event
  * /api/attendance/update
    * Methods: POST
    * Roles with Access: intercept_staff + intercept_system_admin + administrator
    * Internal-only API method
  * /api/attendance/create
    * Methods: POST
    * Roles with Access: intercept_staff + intercept_system_admin + administrator
    * Internal-only API method
  * /api/event/evaluate
    * Methods: POST, DELETE
    * Permissions Required: update own event evaluation+update any event evaluation
    * Internal-only API method
  * /api/event/analysis
    * Methods: POST, GET
    * Permissions Required: analyze events
    * Internal-only API method
* Intercept Room Reservation
  * /api/rooms/reserve
    * Methods: POST, GET
    * Permissions Required: User is logged in
    * Internal-only API method
  * /api/rooms/user/status
    * Methods: POST, GET
    * Permissions Required: User is logged in
    * Internal-only API method
  * /api/rooms/availability
    * Methods: POST, GET
    * Permissions Required: None
    * Public API method
    * Example request body:

```
{"rooms":["cc68f4c2-cc66-408c-9fbb-6ce8aa44ffb8"],"duration":30,"start":"2019-08-13T04:00:00","end":"2019-08-14T03:59:59"}
```

  * Example response:

```{
    "cc68f4c2-cc66-408c-9fbb-6ce8aa44ffb8": {
        "has_reservation_conflict": false,
        "has_open_hours_conflict": false,
        "is_closed": false,
        "closed_message": "Location Closed",
        "has_location": true,
        "dates": {
            "c7073ac9-0ad9-4514-9ec9-1dd31c819323": {
                "start": "2019-08-13T13:00:00",
                "end": "2019-08-13T16:30:00"
            }
        }
    }
}
```


  * /jsonapi/node/room
    * Example GET request:

```
https://dev-richland-site.pantheonsite.io/jsonapi/node/room?filter[status][value]=1&filter[capacity][condition][path]=field_capacity_max&filter[capacity][condition][value]=20&filter[capacity][condition][operator]=>=&filter[field_staff_use_only][value]=false&filter[locations][group][conjunction]=AND&filter[withLocation][condition][path]=field_location&filter[withLocation][condition][value]=&filter[withLocation][condition][operator]=<>&filter[withLocation][condition][memberOf]=locations&filter[onlyBranchLocation][condition][path]=field_location.field_branch_location&filter[onlyBranchLocation][condition][value]=1&filter[onlyBranchLocation][condition][memberOf]=locations&filter[taxonomy_term--room_type][condition][path]=field_room_type.uuid&filter[taxonomy_term--room_type][condition][value][]=92d752af-275f-4c9e-9215-ddf09bcaaab8&filter[taxonomy_term--room_type][condition][operator]=IN&filter[node--location][condition][path]=field_location.uuid&filter[node--location][condition][value][]=ce465283-9203-49da-a342-5ff62af65c31&filter[node--location][condition][value][]=2f900166-5d6e-4f46-a87a-916b4eacff16&filter[node--location][condition][operator]=IN&fields[node--room]=nid,uuid,status,title,created,changed,promote,sticky,path,field_capacity_max,field_capacity_min,field_reservable_online,field_room_fees,field_room_standard_equipment,field_reservation_phone_number,field_staff_use_only,field_text_content,field_text_intro,field_text_teaser,type,uid,image_primary,field_location,field_room_type&include=image_primary,image_primary.field_media_image&sort=title
```

  * Example response:

```
{
    "data": [
        {
            "type": "node--room",
            "id": "cc68f4c2-cc66-408c-9fbb-6ce8aa44ffb8",
            "attributes": {
                "nid": 2805,
                "uuid": "cc68f4c2-cc66-408c-9fbb-6ce8aa44ffb8",
                "status": true,
                "title": "Blythewood Meeting Room",
                "created": 1533239342,
                "changed": 1545058309,
                "promote": false,
                "sticky": false,
                "path": {
                    "alias": "/location/richland-library-blythewood/blythewood-meeting-room",
                    "pid": 4582,
                    "langcode": "en"
                },
                "field_capacity_max": 25,
                "field_capacity_min": 2,
                "field_reservable_online": true,
                "field_reservation_phone_number": "803-691-9806",
                "field_room_fees": null,
                "field_room_standard_equipment": [
                    "25 Chairs",
                    "2 Tables",
                    "Projector and Screen for Presentation",
                    "HDMI cable not provided"
                ],
                "field_staff_use_only": false,
                "field_text_content": null,
                "field_text_intro": null,
                "field_text_teaser": {
                    "value": "<p>Good for large meetings, group activities and small presentations.&nbsp;<strong>This room does have a 3-hour time limit.</strong></p>\r\n",
                    "format": "basic_html",
                    "processed": "<p>Good for large meetings, group activities and small presentations. <strong>This room does have a 3-hour time limit.</strong></p>"
                }
            },
            "relationships": {
                "type": {
                    "data": {
                        "type": "node_type--node_type",
                        "id": "62eb8212-484f-4cd0-a206-1b36127ea0cf"
                    },
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/room/cc68f4c2-cc66-408c-9fbb-6ce8aa44ffb8/relationships/type",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/room/cc68f4c2-cc66-408c-9fbb-6ce8aa44ffb8/type"
                    }
                },
                "uid": {
                    "data": {
                        "type": "user--user",
                        "id": "35891e18-91c6-42fe-a76f-e4177bd506f1"
                    },
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/room/cc68f4c2-cc66-408c-9fbb-6ce8aa44ffb8/relationships/uid",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/room/cc68f4c2-cc66-408c-9fbb-6ce8aa44ffb8/uid"
                    }
                },
                "field_location": {
                    "data": {
                        "type": "node--location",
                        "id": "ce465283-9203-49da-a342-5ff62af65c31"
                    },
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/room/cc68f4c2-cc66-408c-9fbb-6ce8aa44ffb8/relationships/field_location",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/room/cc68f4c2-cc66-408c-9fbb-6ce8aa44ffb8/field_location"
                    }
                },
                "field_room_type": {
                    "data": {
                        "type": "taxonomy_term--room_type",
                        "id": "92d752af-275f-4c9e-9215-ddf09bcaaab8"
                    },
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/room/cc68f4c2-cc66-408c-9fbb-6ce8aa44ffb8/relationships/field_room_type",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/room/cc68f4c2-cc66-408c-9fbb-6ce8aa44ffb8/field_room_type"
                    }
                },
                "image_primary": {
                    "data": {
                        "type": "media--image",
                        "id": "6cc97710-e0a5-43a8-ae70-639dabd9c233"
                    },
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/room/cc68f4c2-cc66-408c-9fbb-6ce8aa44ffb8/relationships/image_primary",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/room/cc68f4c2-cc66-408c-9fbb-6ce8aa44ffb8/image_primary"
                    }
                }
            },
            "links": {
                "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/room/cc68f4c2-cc66-408c-9fbb-6ce8aa44ffb8"
            }
        }
    ],
    "jsonapi": {
        "version": "1.0",
        "meta": {
            "links": {
                "self": "http://jsonapi.org/format/1.0/"
            }
        }
    },
    "links": {
        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/room?fields%5Bnode--room%5D=nid%2Cuuid%2Cstatus%2Ctitle%2Ccreated%2Cchanged%2Cpromote%2Csticky%2Cpath%2Cfield_capacity_max%2Cfield_capacity_min%2Cfield_reservable_online%2Cfield_room_fees%2Cfield_room_standard_equipment%2Cfield_reservation_phone_number%2Cfield_staff_use_only%2Cfield_text_content%2Cfield_text_intro%2Cfield_text_teaser%2Ctype%2Cuid%2Cimage_primary%2Cfield_location%2Cfield_room_type&filter%5Bcapacity%5D%5Bcondition%5D%5Boperator%5D=%3E%3D&filter%5Bcapacity%5D%5Bcondition%5D%5Bpath%5D=field_capacity_max&filter%5Bcapacity%5D%5Bcondition%5D%5Bvalue%5D=20&filter%5Bfield_staff_use_only%5D%5Bvalue%5D=false&filter%5Blocations%5D%5Bgroup%5D%5Bconjunction%5D=AND&filter%5Bnode--location%5D%5Bcondition%5D%5Boperator%5D=IN&filter%5Bnode--location%5D%5Bcondition%5D%5Bpath%5D=field_location.uuid&filter%5Bnode--location%5D%5Bcondition%5D%5Bvalue%5D%5B%5D=2f900166-5d6e-4f46-a87a-916b4eacff16&filter%5Bnode--location%5D%5Bcondition%5D%5Bvalue%5D%5B%5D=ce465283-9203-49da-a342-5ff62af65c31&filter%5BonlyBranchLocation%5D%5Bcondition%5D%5BmemberOf%5D=locations&filter%5BonlyBranchLocation%5D%5Bcondition%5D%5Bpath%5D=field_location.field_branch_location&filter%5BonlyBranchLocation%5D%5Bcondition%5D%5Bvalue%5D=1&filter%5Bstatus%5D%5Bvalue%5D=1&filter%5Btaxonomy_term--room_type%5D%5Bcondition%5D%5Boperator%5D=IN&filter%5Btaxonomy_term--room_type%5D%5Bcondition%5D%5Bpath%5D=field_room_type.uuid&filter%5Btaxonomy_term--room_type%5D%5Bcondition%5D%5Bvalue%5D%5B%5D=92d752af-275f-4c9e-9215-ddf09bcaaab8&filter%5BwithLocation%5D%5Bcondition%5D%5BmemberOf%5D=locations&filter%5BwithLocation%5D%5Bcondition%5D%5Boperator%5D=%3C%3E&filter%5BwithLocation%5D%5Bcondition%5D%5Bpath%5D=field_location&filter%5BwithLocation%5D%5Bcondition%5D%5Bvalue%5D=&include=image_primary%2Cimage_primary.field_media_image&sort=title"
    },
    "included": [
        {
            "type": "media--image",
            "id": "6cc97710-e0a5-43a8-ae70-639dabd9c233",
            "attributes": {
                "mid": 498,
                "uuid": "6cc97710-e0a5-43a8-ae70-639dabd9c233",
                "vid": 598,
                "langcode": "en",
                "revision_created": 1534001752,
                "revision_log_message": null,
                "status": true,
                "name": "Blythewood Meeting Room",
                "created": 1534001707,
                "changed": 1534001707,
                "default_langcode": true,
                "revision_translation_affected": true,
                "metatag": null,
                "path": null,
                "field_media_caption": null,
                "field_media_credit": null
            },
            "relationships": {
                "bundle": {
                    "data": {
                        "type": "media_type--media_type",
                        "id": "7708b33e-75ed-4e5f-bdee-f1181355d035"
                    },
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/media/image/6cc97710-e0a5-43a8-ae70-639dabd9c233/relationships/bundle",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/media/image/6cc97710-e0a5-43a8-ae70-639dabd9c233/bundle"
                    }
                },
                "revision_user": {
                    "data": null,
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/media/image/6cc97710-e0a5-43a8-ae70-639dabd9c233/relationships/revision_user",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/media/image/6cc97710-e0a5-43a8-ae70-639dabd9c233/revision_user"
                    }
                },
                "thumbnail": {
                    "data": {
                        "type": "file--file",
                        "id": "c17f72f5-9197-4c5a-90da-32a2e593a11f",
                        "meta": {
                            "alt": "Thumbnail",
                            "title": "Blythewood Meeting Room",
                            "width": 5137,
                            "height": 3425
                        }
                    },
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/media/image/6cc97710-e0a5-43a8-ae70-639dabd9c233/relationships/thumbnail",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/media/image/6cc97710-e0a5-43a8-ae70-639dabd9c233/thumbnail"
                    }
                },
                "uid": {
                    "data": {
                        "type": "user--user",
                        "id": "03944915-a878-4a25-a223-81bb7ae4031b"
                    },
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/media/image/6cc97710-e0a5-43a8-ae70-639dabd9c233/relationships/uid",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/media/image/6cc97710-e0a5-43a8-ae70-639dabd9c233/uid"
                    }
                },
                "field_media_image": {
                    "data": {
                        "type": "file--file",
                        "id": "c17f72f5-9197-4c5a-90da-32a2e593a11f",
                        "meta": {
                            "alt": "Large meeting room with 25 chairs and 2 tables",
                            "title": "",
                            "width": 5137,
                            "height": 3425
                        }
                    },
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/media/image/6cc97710-e0a5-43a8-ae70-639dabd9c233/relationships/field_media_image",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/media/image/6cc97710-e0a5-43a8-ae70-639dabd9c233/field_media_image"
                    }
                }
            },
            "links": {
                "self": "https://dev-richland-site.pantheonsite.io/jsonapi/media/image/6cc97710-e0a5-43a8-ae70-639dabd9c233"
            }
        },
        {
            "type": "file--file",
            "id": "c17f72f5-9197-4c5a-90da-32a2e593a11f",
            "attributes": {
                "fid": 612,
                "uuid": "c17f72f5-9197-4c5a-90da-32a2e593a11f",
                "langcode": "en",
                "filename": "richland-library-blythewood-photographer-eric-blake-columbiapics--10-of-20_42023545264_o.jpg",
                "uri": {
                    "value": "public://2018-08/richland-library-blythewood-photographer-eric-blake-columbiapics--10-of-20_42023545264_o.jpg",
                    "url": "/sites/default/files/2018-08/richland-library-blythewood-photographer-eric-blake-columbiapics--10-of-20_42023545264_o.jpg"
                },
                "filemime": "image/jpeg",
                "filesize": 8768215,
                "status": true,
                "created": 1534001681,
                "changed": 1534001752,
                "url": "/sites/default/files/2018-08/richland-library-blythewood-photographer-eric-blake-columbiapics--10-of-20_42023545264_o.jpg"
            },
            "relationships": {
                "uid": {
                    "data": {
                        "type": "user--user",
                        "id": "03944915-a878-4a25-a223-81bb7ae4031b"
                    },
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/file/file/c17f72f5-9197-4c5a-90da-32a2e593a11f/relationships/uid",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/file/file/c17f72f5-9197-4c5a-90da-32a2e593a11f/uid"
                    }
                }
            },
            "links": {
                "self": "https://dev-richland-site.pantheonsite.io/jsonapi/file/file/c17f72f5-9197-4c5a-90da-32a2e593a11f"
            }
        }
    ]
}
```
                
  * Events
    * Example GET request:

```
https://dev-richland-site.pantheonsite.io/jsonapi/node/event?filter[status][value]=1&filter[field_event_designation][value]=events&filter[data][condition][path]=field_date_time.value&filter[data][condition][value][]=2019-08-12T04:00:00.000Z&filter[data][condition][value][]=2019-08-18T03:59:59.999Z&filter[data][condition][operator]=BETWEEN&filter[keyword][condition][path]=field_keywords&filter[keyword][condition][value]=Computer&filter[keyword][condition][operator]=CONTAINS&filter[taxonomy_term--event_type][condition][path]=field_event_type.uuid&filter[taxonomy_term--event_type][condition][value][]=6a96b828-c299-4af4-a14b-743b9f377aa4&filter[taxonomy_term--event_type][condition][value][]=ad4a46da-4752-4f58-a44c-fb628c13aa75&filter[taxonomy_term--event_type][condition][value][]=c5dd42d9-7132-40e6-82cb-2f3ddeb428d3&filter[taxonomy_term--event_type][condition][value][]=a8a2e0b8-0732-419d-9503-d56fbd188763&filter[taxonomy_term--event_type][condition][value][]=680d49e1-4a24-4805-9ac3-0a681c303827&filter[taxonomy_term--event_type][condition][value][]=48bcde14-e4c4-41cd-a294-fe0a9df02267&filter[taxonomy_term--event_type][condition][operator]=IN&filter[node--location][condition][path]=field_location.uuid&filter[node--location][condition][value][]=ce465283-9203-49da-a342-5ff62af65c31&filter[node--location][condition][value][]=2f900166-5d6e-4f46-a87a-916b4eacff16&filter[node--location][condition][value][]=65e02a9d-6d64-4ffb-be34-e2b715ab160b&filter[node--location][condition][operator]=IN&filter[taxonomy_term--audience][condition][path]=field_event_audience.uuid&filter[taxonomy_term--audience][condition][value][]=96887d69-800d-4e91-b434-d3d5167985d3&filter[taxonomy_term--audience][condition][value][]=002a9b40-2c35-4c47-b0bc-f75c9b873cd4&filter[taxonomy_term--audience][condition][value][]=354e80c8-6902-4d0f-99f4-dbca85205b25&filter[taxonomy_term--audience][condition][operator]=IN&fields[node--event]=nid,uuid,status,title,path,field_date_time,field_must_register,field_text_teaser,registration,field_event_audience,field_event_register_period,field_event_type,field_event_tags,field_location,field_event_designation,field_room,image_primary&fields[media--image]=mid,uuid,field_media_caption,field_media_credit,field_media_image&fields[file--file]=fid,uuid,uri,url&fields[node--room]=nid,uuid,title,field_location&page[limit]=10&include=image_primary,image_primary.field_media_image,field_room&sort=field_date_time.value
```

  * Example response:

```
{
    "data": [
        {
            "type": "node--event",
            "id": "922a824d-b605-41b6-8652-b44d4e5fb61c",
            "attributes": {
                "nid": 10034,
                "uuid": "922a824d-b605-41b6-8652-b44d4e5fb61c",
                "status": true,
                "title": "Microsoft Word (2016)",
                "path": {
                    "alias": "/event/2019-08-12/microsoft-word-2013",
                    "pid": 16278,
                    "langcode": "en"
                },
                "field_date_time": {
                    "value": "2019-08-12T22:00:00",
                    "end_value": "2019-08-13T00:00:00"
                },
                "field_event_designation": "events",
                "field_event_register_period": {
                    "value": "2019-06-01T16:00:00",
                    "end_value": "2019-08-11T16:00:00"
                },
                "field_must_register": true,
                "field_text_teaser": {
                    "value": "Learn the basics of creating documents using Microsoft Word (2016). Prerequisite: Solid mouse and keyboarding skills.",
                    "format": null,
                    "processed": "<p>Learn the basics of creating documents using Microsoft Word (2016). Prerequisite: Solid mouse and keyboarding skills.</p>\n"
                },
                "registration": {
                    "total": 4,
                    "total_waitlist": 0,
                    "status": "expired",
                    "status_user": null
                }
            },
            "relationships": {
                "field_event_audience": {
                    "data": [
                        {
                            "type": "taxonomy_term--audience",
                            "id": "96887d69-800d-4e91-b434-d3d5167985d3"
                        },
                        {
                            "type": "taxonomy_term--audience",
                            "id": "354e80c8-6902-4d0f-99f4-dbca85205b25"
                        }
                    ],
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/922a824d-b605-41b6-8652-b44d4e5fb61c/relationships/field_event_audience",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/922a824d-b605-41b6-8652-b44d4e5fb61c/field_event_audience"
                    }
                },
                "field_event_tags": {
                    "data": [
                        {
                            "type": "taxonomy_term--tag",
                            "id": "8f0a11a7-234e-4c95-83ef-18f2fc242cf0"
                        }
                    ],
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/922a824d-b605-41b6-8652-b44d4e5fb61c/relationships/field_event_tags",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/922a824d-b605-41b6-8652-b44d4e5fb61c/field_event_tags"
                    }
                },
                "field_event_type": {
                    "data": [
                        {
                            "type": "taxonomy_term--event_type",
                            "id": "c5dd42d9-7132-40e6-82cb-2f3ddeb428d3"
                        }
                    ],
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/922a824d-b605-41b6-8652-b44d4e5fb61c/relationships/field_event_type",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/922a824d-b605-41b6-8652-b44d4e5fb61c/field_event_type"
                    }
                },
                "field_location": {
                    "data": {
                        "type": "node--location",
                        "id": "65e02a9d-6d64-4ffb-be34-e2b715ab160b"
                    },
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/922a824d-b605-41b6-8652-b44d4e5fb61c/relationships/field_location",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/922a824d-b605-41b6-8652-b44d4e5fb61c/field_location"
                    }
                },
                "field_room": {
                    "data": {
                        "type": "node--room",
                        "id": "34dc7f02-0e4f-4616-b68f-53d5d3300d85"
                    },
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/922a824d-b605-41b6-8652-b44d4e5fb61c/relationships/field_room",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/922a824d-b605-41b6-8652-b44d4e5fb61c/field_room"
                    }
                },
                "image_primary": {
                    "data": {
                        "type": "media--image",
                        "id": "a229dee0-fd63-48f8-b844-8140686310d8"
                    },
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/922a824d-b605-41b6-8652-b44d4e5fb61c/relationships/image_primary",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/922a824d-b605-41b6-8652-b44d4e5fb61c/image_primary"
                    }
                }
            },
            "links": {
                "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/922a824d-b605-41b6-8652-b44d4e5fb61c"
            }
        },
        {
            "type": "node--event",
            "id": "e6ae64a4-0c01-4135-a20c-f02b51fa72d3",
            "attributes": {
                "nid": 10058,
                "uuid": "e6ae64a4-0c01-4135-a20c-f02b51fa72d3",
                "status": true,
                "title": "Computer and eReady Basics Learning Lab",
                "path": {
                    "alias": "/event/2019-08-14/computer-and-eready-basics-learning-lab",
                    "pid": 16325,
                    "langcode": "en"
                },
                "field_date_time": {
                    "value": "2019-08-14T22:00:00",
                    "end_value": "2019-08-14T23:00:00"
                },
                "field_event_designation": "events",
                "field_event_register_period": {
                    "value": "2019-05-01T16:00:00",
                    "end_value": "2019-05-16T03:00:00"
                },
                "field_must_register": false,
                "field_text_teaser": {
                    "value": "Do you have a specific question about how to use computers or access Richland Library's electronic resources? Drop-in to our Computer and eReady Basics Learning Lab for personalized assistance. ",
                    "format": null,
                    "processed": "<p>Do you have a specific question about how to use computers or access Richland Library&#039;s electronic resources? Drop-in to our Computer and eReady Basics Learning Lab for personalized assistance.</p>\n"
                },
                "registration": {
                    "total": 0,
                    "total_waitlist": 0,
                    "status": "open",
                    "status_user": null
                }
            },
            "relationships": {
                "field_event_audience": {
                    "data": [
                        {
                            "type": "taxonomy_term--audience",
                            "id": "96887d69-800d-4e91-b434-d3d5167985d3"
                        },
                        {
                            "type": "taxonomy_term--audience",
                            "id": "354e80c8-6902-4d0f-99f4-dbca85205b25"
                        }
                    ],
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/e6ae64a4-0c01-4135-a20c-f02b51fa72d3/relationships/field_event_audience",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/e6ae64a4-0c01-4135-a20c-f02b51fa72d3/field_event_audience"
                    }
                },
                "field_event_tags": {
                    "data": [],
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/e6ae64a4-0c01-4135-a20c-f02b51fa72d3/relationships/field_event_tags",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/e6ae64a4-0c01-4135-a20c-f02b51fa72d3/field_event_tags"
                    }
                },
                "field_event_type": {
                    "data": [
                        {
                            "type": "taxonomy_term--event_type",
                            "id": "c5dd42d9-7132-40e6-82cb-2f3ddeb428d3"
                        }
                    ],
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/e6ae64a4-0c01-4135-a20c-f02b51fa72d3/relationships/field_event_type",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/e6ae64a4-0c01-4135-a20c-f02b51fa72d3/field_event_type"
                    }
                },
                "field_location": {
                    "data": {
                        "type": "node--location",
                        "id": "65e02a9d-6d64-4ffb-be34-e2b715ab160b"
                    },
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/e6ae64a4-0c01-4135-a20c-f02b51fa72d3/relationships/field_location",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/e6ae64a4-0c01-4135-a20c-f02b51fa72d3/field_location"
                    }
                },
                "field_room": {
                    "data": {
                        "type": "node--room",
                        "id": "34dc7f02-0e4f-4616-b68f-53d5d3300d85"
                    },
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/e6ae64a4-0c01-4135-a20c-f02b51fa72d3/relationships/field_room",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/e6ae64a4-0c01-4135-a20c-f02b51fa72d3/field_room"
                    }
                },
                "image_primary": {
                    "data": {
                        "type": "media--image",
                        "id": "08e05585-f1a9-41be-a8b4-a5ddb204b0cd"
                    },
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/e6ae64a4-0c01-4135-a20c-f02b51fa72d3/relationships/image_primary",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/e6ae64a4-0c01-4135-a20c-f02b51fa72d3/image_primary"
                    }
                }
            },
            "links": {
                "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/e6ae64a4-0c01-4135-a20c-f02b51fa72d3"
            }
        },
        {
            "type": "node--event",
            "id": "84e1e1fa-1ffc-4e61-8479-bef0cba2c990",
            "attributes": {
                "nid": 11232,
                "uuid": "84e1e1fa-1ffc-4e61-8479-bef0cba2c990",
                "status": true,
                "title": "Production Stage Orientation",
                "path": {
                    "alias": "/event/2019-08-15/production-stage-orientation",
                    "pid": 18545,
                    "langcode": "en"
                },
                "field_date_time": {
                    "value": "2019-08-15T22:00:00",
                    "end_value": "2019-08-15T23:00:00"
                },
                "field_event_designation": "events",
                "field_event_register_period": null,
                "field_must_register": false,
                "field_text_teaser": {
                    "value": "Get certified to use the Main Library's Production Stage. ",
                    "format": null,
                    "processed": "<p>Get certified to use the Main Library&#039;s Production Stage.</p>\n"
                },
                "registration": {
                    "total": 0,
                    "total_waitlist": 0,
                    "status": "open",
                    "status_user": null
                }
            },
            "relationships": {
                "field_event_audience": {
                    "data": [
                        {
                            "type": "taxonomy_term--audience",
                            "id": "96887d69-800d-4e91-b434-d3d5167985d3"
                        }
                    ],
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/84e1e1fa-1ffc-4e61-8479-bef0cba2c990/relationships/field_event_audience",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/84e1e1fa-1ffc-4e61-8479-bef0cba2c990/field_event_audience"
                    }
                },
                "field_event_tags": {
                    "data": [],
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/84e1e1fa-1ffc-4e61-8479-bef0cba2c990/relationships/field_event_tags",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/84e1e1fa-1ffc-4e61-8479-bef0cba2c990/field_event_tags"
                    }
                },
                "field_event_type": {
                    "data": [
                        {
                            "type": "taxonomy_term--event_type",
                            "id": "ad4a46da-4752-4f58-a44c-fb628c13aa75"
                        },
                        {
                            "type": "taxonomy_term--event_type",
                            "id": "c5dd42d9-7132-40e6-82cb-2f3ddeb428d3"
                        }
                    ],
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/84e1e1fa-1ffc-4e61-8479-bef0cba2c990/relationships/field_event_type",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/84e1e1fa-1ffc-4e61-8479-bef0cba2c990/field_event_type"
                    }
                },
                "field_location": {
                    "data": {
                        "type": "node--location",
                        "id": "65e02a9d-6d64-4ffb-be34-e2b715ab160b"
                    },
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/84e1e1fa-1ffc-4e61-8479-bef0cba2c990/relationships/field_location",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/84e1e1fa-1ffc-4e61-8479-bef0cba2c990/field_location"
                    }
                },
                "field_room": {
                    "data": {
                        "type": "node--room",
                        "id": "4ea3ee5d-bdfd-4beb-b694-9184ce990300"
                    },
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/84e1e1fa-1ffc-4e61-8479-bef0cba2c990/relationships/field_room",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/84e1e1fa-1ffc-4e61-8479-bef0cba2c990/field_room"
                    }
                },
                "image_primary": {
                    "data": {
                        "type": "media--image",
                        "id": "915bb5ee-a767-4da0-bfce-032eb90fe78c"
                    },
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/84e1e1fa-1ffc-4e61-8479-bef0cba2c990/relationships/image_primary",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/84e1e1fa-1ffc-4e61-8479-bef0cba2c990/image_primary"
                    }
                }
            },
            "links": {
                "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/84e1e1fa-1ffc-4e61-8479-bef0cba2c990"
            }
        },
        {
            "type": "node--event",
            "id": "507c332e-9940-4d2b-9826-74a00eac8e81",
            "attributes": {
                "nid": 10048,
                "uuid": "507c332e-9940-4d2b-9826-74a00eac8e81",
                "status": true,
                "title": "Microsoft Publisher (2016)",
                "path": {
                    "alias": "/event/2019-08-15/microsoft-publisher-2013",
                    "pid": 16306,
                    "langcode": "en"
                },
                "field_date_time": {
                    "value": "2019-08-15T22:00:00",
                    "end_value": "2019-08-16T00:00:00"
                },
                "field_event_designation": "events",
                "field_event_register_period": {
                    "value": "2019-06-01T16:00:00",
                    "end_value": "2019-08-15T03:00:00"
                },
                "field_must_register": true,
                "field_text_teaser": {
                    "value": "Learn how to use Microsoft Publisher (2016) to create newsletters, flyers, and brochures. ",
                    "format": null,
                    "processed": "<p>Learn how to use Microsoft Publisher (2016) to create newsletters, flyers, and brochures.</p>\n"
                },
                "registration": {
                    "total": 6,
                    "total_waitlist": 0,
                    "status": "open",
                    "status_user": null
                }
            },
            "relationships": {
                "field_event_audience": {
                    "data": [
                        {
                            "type": "taxonomy_term--audience",
                            "id": "96887d69-800d-4e91-b434-d3d5167985d3"
                        },
                        {
                            "type": "taxonomy_term--audience",
                            "id": "354e80c8-6902-4d0f-99f4-dbca85205b25"
                        }
                    ],
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/507c332e-9940-4d2b-9826-74a00eac8e81/relationships/field_event_audience",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/507c332e-9940-4d2b-9826-74a00eac8e81/field_event_audience"
                    }
                },
                "field_event_tags": {
                    "data": [
                        {
                            "type": "taxonomy_term--tag",
                            "id": "8f0a11a7-234e-4c95-83ef-18f2fc242cf0"
                        }
                    ],
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/507c332e-9940-4d2b-9826-74a00eac8e81/relationships/field_event_tags",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/507c332e-9940-4d2b-9826-74a00eac8e81/field_event_tags"
                    }
                },
                "field_event_type": {
                    "data": [
                        {
                            "type": "taxonomy_term--event_type",
                            "id": "c5dd42d9-7132-40e6-82cb-2f3ddeb428d3"
                        }
                    ],
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/507c332e-9940-4d2b-9826-74a00eac8e81/relationships/field_event_type",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/507c332e-9940-4d2b-9826-74a00eac8e81/field_event_type"
                    }
                },
                "field_location": {
                    "data": {
                        "type": "node--location",
                        "id": "65e02a9d-6d64-4ffb-be34-e2b715ab160b"
                    },
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/507c332e-9940-4d2b-9826-74a00eac8e81/relationships/field_location",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/507c332e-9940-4d2b-9826-74a00eac8e81/field_location"
                    }
                },
                "field_room": {
                    "data": {
                        "type": "node--room",
                        "id": "34dc7f02-0e4f-4616-b68f-53d5d3300d85"
                    },
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/507c332e-9940-4d2b-9826-74a00eac8e81/relationships/field_room",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/507c332e-9940-4d2b-9826-74a00eac8e81/field_room"
                    }
                },
                "image_primary": {
                    "data": {
                        "type": "media--image",
                        "id": "33b1b020-a8d6-4c88-8186-d6ccf750bc41"
                    },
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/507c332e-9940-4d2b-9826-74a00eac8e81/relationships/image_primary",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/507c332e-9940-4d2b-9826-74a00eac8e81/image_primary"
                    }
                }
            },
            "links": {
                "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/507c332e-9940-4d2b-9826-74a00eac8e81"
            }
        },
        {
            "type": "node--event",
            "id": "b051139e-ab40-4542-a31b-9a7f466ad70c",
            "attributes": {
                "nid": 10083,
                "uuid": "b051139e-ab40-4542-a31b-9a7f466ad70c",
                "status": true,
                "title": "Circuit Building with IT-oLogy",
                "path": {
                    "alias": "/event/2019-08-15/circuit-building-it-ology",
                    "pid": 16374,
                    "langcode": "en"
                },
                "field_date_time": {
                    "value": "2019-08-15T22:00:00",
                    "end_value": "2019-08-15T23:30:00"
                },
                "field_event_designation": "events",
                "field_event_register_period": {
                    "value": "2019-06-15T04:00:00",
                    "end_value": "2019-08-15T20:00:00"
                },
                "field_must_register": true,
                "field_text_teaser": {
                    "value": "Students will learn the fundamental components of a circuit and a practical understanding of electronics to build a circuit.",
                    "format": null,
                    "processed": "<p>Students will learn the fundamental components of a circuit and a practical understanding of electronics to build a circuit.</p>\n"
                },
                "registration": {
                    "total": 10,
                    "total_waitlist": 2,
                    "status": "waitlist",
                    "status_user": null
                }
            },
            "relationships": {
                "field_event_audience": {
                    "data": [
                        {
                            "type": "taxonomy_term--audience",
                            "id": "96887d69-800d-4e91-b434-d3d5167985d3"
                        },
                        {
                            "type": "taxonomy_term--audience",
                            "id": "d703c9f3-2e30-4df0-86c2-adecd137f14b"
                        }
                    ],
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/b051139e-ab40-4542-a31b-9a7f466ad70c/relationships/field_event_audience",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/b051139e-ab40-4542-a31b-9a7f466ad70c/field_event_audience"
                    }
                },
                "field_event_tags": {
                    "data": [
                        {
                            "type": "taxonomy_term--tag",
                            "id": "8f0a11a7-234e-4c95-83ef-18f2fc242cf0"
                        }
                    ],
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/b051139e-ab40-4542-a31b-9a7f466ad70c/relationships/field_event_tags",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/b051139e-ab40-4542-a31b-9a7f466ad70c/field_event_tags"
                    }
                },
                "field_event_type": {
                    "data": [
                        {
                            "type": "taxonomy_term--event_type",
                            "id": "c5dd42d9-7132-40e6-82cb-2f3ddeb428d3"
                        }
                    ],
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/b051139e-ab40-4542-a31b-9a7f466ad70c/relationships/field_event_type",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/b051139e-ab40-4542-a31b-9a7f466ad70c/field_event_type"
                    }
                },
                "field_location": {
                    "data": {
                        "type": "node--location",
                        "id": "ce465283-9203-49da-a342-5ff62af65c31"
                    },
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/b051139e-ab40-4542-a31b-9a7f466ad70c/relationships/field_location",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/b051139e-ab40-4542-a31b-9a7f466ad70c/field_location"
                    }
                },
                "field_room": {
                    "data": {
                        "type": "node--room",
                        "id": "cc68f4c2-cc66-408c-9fbb-6ce8aa44ffb8"
                    },
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/b051139e-ab40-4542-a31b-9a7f466ad70c/relationships/field_room",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/b051139e-ab40-4542-a31b-9a7f466ad70c/field_room"
                    }
                },
                "image_primary": {
                    "data": {
                        "type": "media--image",
                        "id": "4715ef24-c9ba-4676-988e-c6908f278fdd"
                    },
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/b051139e-ab40-4542-a31b-9a7f466ad70c/relationships/image_primary",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/b051139e-ab40-4542-a31b-9a7f466ad70c/image_primary"
                    }
                }
            },
            "links": {
                "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event/b051139e-ab40-4542-a31b-9a7f466ad70c"
            }
        }
    ],
    "jsonapi": {
        "version": "1.0",
        "meta": {
            "links": {
                "self": "http://jsonapi.org/format/1.0/"
            }
        }
    },
    "links": {
        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/event?fields%5Bfile--file%5D=fid%2Cuuid%2Curi%2Curl&fields%5Bmedia--image%5D=mid%2Cuuid%2Cfield_media_caption%2Cfield_media_credit%2Cfield_media_image&fields%5Bnode--event%5D=nid%2Cuuid%2Cstatus%2Ctitle%2Cpath%2Cfield_date_time%2Cfield_must_register%2Cfield_text_teaser%2Cregistration%2Cfield_event_audience%2Cfield_event_register_period%2Cfield_event_type%2Cfield_event_tags%2Cfield_location%2Cfield_event_designation%2Cfield_room%2Cimage_primary&fields%5Bnode--room%5D=nid%2Cuuid%2Ctitle%2Cfield_location&filter%5Bdata%5D%5Bcondition%5D%5Boperator%5D=BETWEEN&filter%5Bdata%5D%5Bcondition%5D%5Bpath%5D=field_date_time.value&filter%5Bdata%5D%5Bcondition%5D%5Bvalue%5D%5B%5D=2019-08-12T04%3A00%3A00.000Z&filter%5Bdata%5D%5Bcondition%5D%5Bvalue%5D%5B%5D=2019-08-18T03%3A59%3A59.999Z&filter%5Bfield_event_designation%5D%5Bvalue%5D=events&filter%5Bkeyword%5D%5Bcondition%5D%5Boperator%5D=CONTAINS&filter%5Bkeyword%5D%5Bcondition%5D%5Bpath%5D=field_keywords&filter%5Bkeyword%5D%5Bcondition%5D%5Bvalue%5D=Computer&filter%5Bnode--location%5D%5Bcondition%5D%5Boperator%5D=IN&filter%5Bnode--location%5D%5Bcondition%5D%5Bpath%5D=field_location.uuid&filter%5Bnode--location%5D%5Bcondition%5D%5Bvalue%5D%5B%5D=2f900166-5d6e-4f46-a87a-916b4eacff16&filter%5Bnode--location%5D%5Bcondition%5D%5Bvalue%5D%5B%5D=65e02a9d-6d64-4ffb-be34-e2b715ab160b&filter%5Bnode--location%5D%5Bcondition%5D%5Bvalue%5D%5B%5D=ce465283-9203-49da-a342-5ff62af65c31&filter%5Bstatus%5D%5Bvalue%5D=1&filter%5Btaxonomy_term--audience%5D%5Bcondition%5D%5Boperator%5D=IN&filter%5Btaxonomy_term--audience%5D%5Bcondition%5D%5Bpath%5D=field_event_audience.uuid&filter%5Btaxonomy_term--audience%5D%5Bcondition%5D%5Bvalue%5D%5B%5D=002a9b40-2c35-4c47-b0bc-f75c9b873cd4&filter%5Btaxonomy_term--audience%5D%5Bcondition%5D%5Bvalue%5D%5B%5D=354e80c8-6902-4d0f-99f4-dbca85205b25&filter%5Btaxonomy_term--audience%5D%5Bcondition%5D%5Bvalue%5D%5B%5D=96887d69-800d-4e91-b434-d3d5167985d3&filter%5Btaxonomy_term--event_type%5D%5Bcondition%5D%5Boperator%5D=IN&filter%5Btaxonomy_term--event_type%5D%5Bcondition%5D%5Bpath%5D=field_event_type.uuid&filter%5Btaxonomy_term--event_type%5D%5Bcondition%5D%5Bvalue%5D%5B%5D=48bcde14-e4c4-41cd-a294-fe0a9df02267&filter%5Btaxonomy_term--event_type%5D%5Bcondition%5D%5Bvalue%5D%5B%5D=680d49e1-4a24-4805-9ac3-0a681c303827&filter%5Btaxonomy_term--event_type%5D%5Bcondition%5D%5Bvalue%5D%5B%5D=6a96b828-c299-4af4-a14b-743b9f377aa4&filter%5Btaxonomy_term--event_type%5D%5Bcondition%5D%5Bvalue%5D%5B%5D=a8a2e0b8-0732-419d-9503-d56fbd188763&filter%5Btaxonomy_term--event_type%5D%5Bcondition%5D%5Bvalue%5D%5B%5D=ad4a46da-4752-4f58-a44c-fb628c13aa75&filter%5Btaxonomy_term--event_type%5D%5Bcondition%5D%5Bvalue%5D%5B%5D=c5dd42d9-7132-40e6-82cb-2f3ddeb428d3&include=image_primary%2Cimage_primary.field_media_image%2Cfield_room&page%5Blimit%5D=10&sort=field_date_time.value"
    },
    "included": [
        {
            "type": "node--room",
            "id": "34dc7f02-0e4f-4616-b68f-53d5d3300d85",
            "attributes": {
                "nid": 2800,
                "uuid": "34dc7f02-0e4f-4616-b68f-53d5d3300d85",
                "title": "Business, Careers and Research Classroom"
            },
            "relationships": {
                "field_location": {
                    "data": {
                        "type": "node--location",
                        "id": "65e02a9d-6d64-4ffb-be34-e2b715ab160b"
                    },
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/room/34dc7f02-0e4f-4616-b68f-53d5d3300d85/relationships/field_location",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/room/34dc7f02-0e4f-4616-b68f-53d5d3300d85/field_location"
                    }
                }
            },
            "links": {
                "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/room/34dc7f02-0e4f-4616-b68f-53d5d3300d85"
            }
        },
        {
            "type": "media--image",
            "id": "a229dee0-fd63-48f8-b844-8140686310d8",
            "attributes": {
                "mid": 216,
                "uuid": "a229dee0-fd63-48f8-b844-8140686310d8",
                "field_media_caption": null,
                "field_media_credit": null
            },
            "relationships": {
                "field_media_image": {
                    "data": {
                        "type": "file--file",
                        "id": "6ed7d68f-a816-4bcd-987d-1a991fb17cb8",
                        "meta": {
                            "alt": "Customers sitting at computer.",
                            "title": "",
                            "width": 2000,
                            "height": 1333
                        }
                    },
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/media/image/a229dee0-fd63-48f8-b844-8140686310d8/relationships/field_media_image",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/media/image/a229dee0-fd63-48f8-b844-8140686310d8/field_media_image"
                    }
                }
            },
            "links": {
                "self": "https://dev-richland-site.pantheonsite.io/jsonapi/media/image/a229dee0-fd63-48f8-b844-8140686310d8"
            }
        },
        {
            "type": "file--file",
            "id": "6ed7d68f-a816-4bcd-987d-1a991fb17cb8",
            "attributes": {
                "fid": 272,
                "uuid": "6ed7d68f-a816-4bcd-987d-1a991fb17cb8",
                "uri": {
                    "value": "public://2018-08/events_computerbasics_adults_5.jpg",
                    "url": "/sites/default/files/2018-08/events_computerbasics_adults_5.jpg"
                },
                "url": "/sites/default/files/2018-08/events_computerbasics_adults_5.jpg"
            },
            "links": {
                "self": "https://dev-richland-site.pantheonsite.io/jsonapi/file/file/6ed7d68f-a816-4bcd-987d-1a991fb17cb8"
            }
        },
        {
            "type": "media--image",
            "id": "08e05585-f1a9-41be-a8b4-a5ddb204b0cd",
            "attributes": {
                "mid": 221,
                "uuid": "08e05585-f1a9-41be-a8b4-a5ddb204b0cd",
                "field_media_caption": null,
                "field_media_credit": null
            },
            "relationships": {
                "field_media_image": {
                    "data": {
                        "type": "file--file",
                        "id": "d5009552-5b85-4d55-9951-932831c8ed27",
                        "meta": {
                            "alt": "Two staff assist a customer on a computer",
                            "title": "",
                            "width": 900,
                            "height": 600
                        }
                    },
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/media/image/08e05585-f1a9-41be-a8b4-a5ddb204b0cd/relationships/field_media_image",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/media/image/08e05585-f1a9-41be-a8b4-a5ddb204b0cd/field_media_image"
                    }
                }
            },
            "links": {
                "self": "https://dev-richland-site.pantheonsite.io/jsonapi/media/image/08e05585-f1a9-41be-a8b4-a5ddb204b0cd"
            }
        },
        {
            "type": "file--file",
            "id": "d5009552-5b85-4d55-9951-932831c8ed27",
            "attributes": {
                "fid": 277,
                "uuid": "d5009552-5b85-4d55-9951-932831c8ed27",
                "uri": {
                    "value": "public://2018-08/Resume Help 01_small.png",
                    "url": "/sites/default/files/2018-08/Resume%20Help%2001_small.png"
                },
                "url": "/sites/default/files/2018-08/Resume%20Help%2001_small.png"
            },
            "links": {
                "self": "https://dev-richland-site.pantheonsite.io/jsonapi/file/file/d5009552-5b85-4d55-9951-932831c8ed27"
            }
        },
        {
            "type": "media--image",
            "id": "915bb5ee-a767-4da0-bfce-032eb90fe78c",
            "attributes": {
                "mid": 1042,
                "uuid": "915bb5ee-a767-4da0-bfce-032eb90fe78c",
                "field_media_caption": null,
                "field_media_credit": null
            },
            "relationships": {
                "field_media_image": {
                    "data": {
                        "type": "file--file",
                        "id": "79dbc063-2bcf-4e27-95ec-5e41a92b4e8f",
                        "meta": {
                            "alt": "Production Stage",
                            "title": "",
                            "width": 1685,
                            "height": 752
                        }
                    },
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/media/image/915bb5ee-a767-4da0-bfce-032eb90fe78c/relationships/field_media_image",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/media/image/915bb5ee-a767-4da0-bfce-032eb90fe78c/field_media_image"
                    }
                }
            },
            "links": {
                "self": "https://dev-richland-site.pantheonsite.io/jsonapi/media/image/915bb5ee-a767-4da0-bfce-032eb90fe78c"
            }
        },
        {
            "type": "file--file",
            "id": "79dbc063-2bcf-4e27-95ec-5e41a92b4e8f",
            "attributes": {
                "fid": 1430,
                "uuid": "79dbc063-2bcf-4e27-95ec-5e41a92b4e8f",
                "uri": {
                    "value": "public://2018-11/Production Stage.JPG",
                    "url": "/sites/default/files/2018-11/Production%20Stage.JPG"
                },
                "url": "/sites/default/files/2018-11/Production%20Stage.JPG"
            },
            "links": {
                "self": "https://dev-richland-site.pantheonsite.io/jsonapi/file/file/79dbc063-2bcf-4e27-95ec-5e41a92b4e8f"
            }
        },
        {
            "type": "media--image",
            "id": "33b1b020-a8d6-4c88-8186-d6ccf750bc41",
            "attributes": {
                "mid": 217,
                "uuid": "33b1b020-a8d6-4c88-8186-d6ccf750bc41",
                "field_media_caption": null,
                "field_media_credit": null
            },
            "relationships": {
                "field_media_image": {
                    "data": {
                        "type": "file--file",
                        "id": "d4dd53e0-f917-4131-8ec9-f477c014d54c",
                        "meta": {
                            "alt": "Woman at desktop computer.",
                            "title": "",
                            "width": 2000,
                            "height": 1324
                        }
                    },
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/media/image/33b1b020-a8d6-4c88-8186-d6ccf750bc41/relationships/field_media_image",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/media/image/33b1b020-a8d6-4c88-8186-d6ccf750bc41/field_media_image"
                    }
                }
            },
            "links": {
                "self": "https://dev-richland-site.pantheonsite.io/jsonapi/media/image/33b1b020-a8d6-4c88-8186-d6ccf750bc41"
            }
        },
        {
            "type": "file--file",
            "id": "d4dd53e0-f917-4131-8ec9-f477c014d54c",
            "attributes": {
                "fid": 273,
                "uuid": "d4dd53e0-f917-4131-8ec9-f477c014d54c",
                "uri": {
                    "value": "public://2018-08/events_computerbasics_adults_1.jpg",
                    "url": "/sites/default/files/2018-08/events_computerbasics_adults_1.jpg"
                },
                "url": "/sites/default/files/2018-08/events_computerbasics_adults_1.jpg"
            },
            "links": {
                "self": "https://dev-richland-site.pantheonsite.io/jsonapi/file/file/d4dd53e0-f917-4131-8ec9-f477c014d54c"
            }
        },
        {
            "type": "node--room",
            "id": "cc68f4c2-cc66-408c-9fbb-6ce8aa44ffb8",
            "attributes": {
                "nid": 2805,
                "uuid": "cc68f4c2-cc66-408c-9fbb-6ce8aa44ffb8",
                "title": "Blythewood Meeting Room"
            },
            "relationships": {
                "field_location": {
                    "data": {
                        "type": "node--location",
                        "id": "ce465283-9203-49da-a342-5ff62af65c31"
                    },
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/room/cc68f4c2-cc66-408c-9fbb-6ce8aa44ffb8/relationships/field_location",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/node/room/cc68f4c2-cc66-408c-9fbb-6ce8aa44ffb8/field_location"
                    }
                }
            },
            "links": {
                "self": "https://dev-richland-site.pantheonsite.io/jsonapi/node/room/cc68f4c2-cc66-408c-9fbb-6ce8aa44ffb8"
            }
        },
        {
            "type": "media--image",
            "id": "4715ef24-c9ba-4676-988e-c6908f278fdd",
            "attributes": {
                "mid": 629,
                "uuid": "4715ef24-c9ba-4676-988e-c6908f278fdd",
                "field_media_caption": null,
                "field_media_credit": null
            },
            "relationships": {
                "field_media_image": {
                    "data": {
                        "type": "file--file",
                        "id": "0e6b1677-cb13-48c7-80d3-dc5dbc05b53a",
                        "meta": {
                            "alt": "Studio Services",
                            "title": "",
                            "width": 5184,
                            "height": 3456
                        }
                    },
                    "links": {
                        "self": "https://dev-richland-site.pantheonsite.io/jsonapi/media/image/4715ef24-c9ba-4676-988e-c6908f278fdd/relationships/field_media_image",
                        "related": "https://dev-richland-site.pantheonsite.io/jsonapi/media/image/4715ef24-c9ba-4676-988e-c6908f278fdd/field_media_image"
                    }
                }
            },
            "links": {
                "self": "https://dev-richland-site.pantheonsite.io/jsonapi/media/image/4715ef24-c9ba-4676-988e-c6908f278fdd"
            }
        },
        {
            "type": "file--file",
            "id": "0e6b1677-cb13-48c7-80d3-dc5dbc05b53a",
            "attributes": {
                "fid": 812,
                "uuid": "0e6b1677-cb13-48c7-80d3-dc5dbc05b53a",
                "uri": {
                    "value": "public://2018-08/studio_services.jpg",
                    "url": "/sites/default/files/2018-08/studio_services.jpg"
                },
                "url": "/sites/default/files/2018-08/studio_services.jpg"
            },
            "links": {
                "self": "https://dev-richland-site.pantheonsite.io/jsonapi/file/file/0e6b1677-cb13-48c7-80d3-dc5dbc05b53a"
            }
        }
    ],
    "meta": {
        "errors": [
            {
                "title": "Forbidden",
                "status": 403,
                "detail": "The current user is not allowed to GET the selected resource.",
                "links": {
                    "info": "http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.4.4"
                },
                "code": 0,
                "id": "/node--room/4ea3ee5d-bdfd-4beb-b694-9184ce990300",
                "source": {
                    "pointer": "/data"
                }
            }
        ]
    }
}
```


INTERCEPT EVENT AND ROOM FIELD NAMES
------------------------------------

Since querying for Events and Rooms is a common practice with Intercept using
JSON API-style query endpoints, a list of field names that can be used in your
queries are included here. For latest list of fields, you can always check that
at Manage > Structure > Content Types > Manage Fields.

* Events
  * `field_add_to_cal`
  * `field_attendees`
  * `field_event_audience`
  * `field_capacity_max`
  * `field_date_time`
  * `field_event_designation`
  * `field_event_series`
  * `field_event_type`
  * `field_featured`
  * `field_text_intro`
  * `field_keywords`
  * `field_location`
  * `field_presenter`
  * `field_audience_primary`
  * `field_text_content`
  * `field_event_type_primary`
  * `image_primary`
  * `field_event_register_period`
  * `field_must_register`
  * `field_room`
  * `field_event_tags`
  * `field_text_teaser`
  * `field_event_is_template`
  * `field_has_waitlist`
  * `field_waitlist_max`
  * `field_event_user_reg_max`
  * `field_presented_by_non_staff`
* Rooms
  * `field_approval_required`
  * `field_room_standard_equipment`
  * `field_room_fees`
  * `field_text_intro`
  * `field_location`
  * `field_capacity_max`
  * `field_capacity_min`
  * `field_text_content`
  * `image_primary`
  * `field_reservable_online`
  * `field_reservation_phone_number`
  * `field_room_type`
  * `field_staff_use_only`
  * `field_text_teaser`


FINDING UUIDS FOR USE IN QUERIES
--------------------------------
If you find yourself writing a query such as the example queries above, you
may find that you need to know the UUID (or universally unique identifier) for
a particular piece of content on your Intercept site. There are a few different
ways to locate this value but perhaps the easiest is to query your database
directly using an application such as Sequel Pro. For example, if you wanted to
locate the UUID for a piece of content with the title "Library Staff Conference
Room", you could try a query like this:

`SELECT n.uuid
FROM node AS n
LEFT JOIN node_field_data AS nfd
ON n.nid = nfd.nid
WHERE nfd.title = 'Library Staff Conference Room';`


RSS FEEDS FOR DIGITAL SIGNAGE
-----------------------------

If your library is planning to display events and room reservations on digital signage, a few dynamic RSS feeds are provided out-of-the-box with Intercept as outlined below.

* Events
  * Path: `/events/feed`
  * Accepted Query Parameters:
    * `location_id`
    * `room_id`
    * `featured`
  * Example: `/events/feed?location_id=2790&room_id=2811&featured=1`
* Room Reservations
  * Path: `/room-reservations/feed`
  * Accepted Query Parameters:
    * `location_id`
    * `room_id`
  * Example: `/room-reservations/feed?location_id=2790&room_id=2811`


MAINTAINERS
-----------

Current maintainers:
  * John Ferris (pixelwhip) - https://www.drupal.org/u/pixelwhip
  * Mark W. Jarrell (attheshow) - https://www.drupal.org/u/attheshow
  * Kay Thayer (kaythay) - https://www.drupal.org/u/kaythay
  * Tyler Youngblood (tyleryoungblood) - https://www.drupal.org/u/tyleryoungblood

This project has been sponsored by:
  * Richland Library (https://www.richlandlibrary.com)
  * Aten Design Group (https://atendesigngroup.com)
  * The Knight Foundation (https://knightfoundation.org)
