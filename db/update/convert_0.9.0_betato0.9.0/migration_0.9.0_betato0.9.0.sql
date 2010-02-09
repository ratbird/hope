# mysql migration script
# base version: 0.9.0-beta
# update version: 0.9

# #24
# changes to the field Veranstaltungsnummer
# 

ALTER TABLE Seminare CHANGE VeranstaltungsNummer eranstaltungsNummer VARCHAR( 32 ) DEFAULT NULL