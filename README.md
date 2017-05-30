FM4 tracklist
============================

Version 3, 30.Mai 2017
=========================
umstellung auf dei neue FM4-Website mit der AudioAPI-Schnittstelle https://audioapi.orf.at/fm4/api/json/current/live

UPGRADE:
mysql -u root fm4 -e 'alter table playtime add column idfm4 bigint'

Version 2, September 2016
=========================

UPGRADE:
mysql -u root fm4 -e 'alter table playtime modify track bigint(20) null'


