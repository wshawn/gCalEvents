Please report bugs & feature requests on Github:
https://github.com/eelkevdbos/gCalEvents

/* DESCRIPTION */

gCalEvents is a component that requests a Google Calendar eventfeed. It enables you to easily integrate google calendar items into your website.

It can be used to extract raw data from the feed and optionally parse the data to format it. To speed up the code, caching can be enabled.

Both public and private feeds can be used. For private feeds you can either supply your privateCookie in the snippet call or store it like a setting.

/* BASIC USAGE */

[[!showAgenda &userID=`yourcalendaremail%40yourdomain.tld` &cached=`1` &decay=`86400` &limit=`5`]] // for public calendars and caching enabled (1 day decay)

[[!showAgenda &userID=`yourcalendaremail%40yourdomain.tld` &privateCookie=`cookievalue`]] // for private calendars

[[!showAgenda &useSettings=`1`]] // to use system settings

/* ADVANCED PARAMETERS */

eventTpl, defines a new template for each event
wrapTpl, defines the wrapper for the events

startDate, to set a minimum startdate, format: Y-m-d\TH:i:s
endDate, to set a maximum enddate, format: see startDate

includeAttendees, to include a list of attendees
cacheSlot, to give each cache item a unique slot for caching, useful when loading multiple agenda's on 1 resource

/* GENERAL */

The eventchunk has all the placeholders google calendar specifies in it's feed. A complete list of keys can be found at: 
http://code.google.com/intl/nl-NL/apis/calendar/data/2.0/developers_guide_protocol.html#RetrievingEvents

Multi-level feeds are concatenated with a dot as key. The entries are concatenated with a ', ' as separator.

Example:
Feed: {'attendees':[{'displayName':'John Doe'}, {'displayName':'Henry Lloyd'}]}
Placeholder: [[+attendees.displayName]]
Result: John Doe, Henry Lloyd