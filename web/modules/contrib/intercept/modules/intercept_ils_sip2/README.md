Remember to add the following keys to your site in order for the module to work.
These should be added at /admin/config/system/keys.

* INTERCEPT_ILS_SIP2_HOST
* INTERCEPT_ILS_SIP2_PORT
* INTERCEPT_ILS_SIP2_USERNAME
* INTERCEPT_ILS_SIP2_PASSWORD
* INTERCEPT_ILS_SIP2_INSTITUTION_ID
* INTERCEPT_ILS_SIP2_LOCATIONS

For all keys other than the last, the type should be set as "Authentication".
For the last key "INTERCEPT_ILS_SIP2_LOCATIONS", the type should be
set as "Authentication (Multivalue)". The value needs to be set as a JSON object
that contains a list of all of your public locations and their location codes.
Be sure to remove all extra white space and line breaks from this JSON.
For example: 

```
{"Branch 1": "1", "Branch 2": "2", "Branch 3": "3"}
```