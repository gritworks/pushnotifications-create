#!/bin/bash
# drag and drop this script in the terminal
RED='\033[0;31m'
NC='\033[0m' # No Color
echo -e "${RED}"
echo -e "** !!important!! ***"
echo -e "private.key is tightly coupled with the CSR.certSigningRequest.
Using the same aps_development.cer or aps.cer from a previous convertion results in
handshake verification error on ssl. So generate a new aps_development.cer
from https://developer.apple.com/account portal with the freshly generated
temp/CSR.certSigningRequest"
echo -e "** !!important!! ***"
echo -e "${NC}"
APP_ID=""
APS_CER_NAME=""
APPLE_API_PUSH=""
# the current app id
echo ""

echo "select type:"
select type in production development
do
   echo $type
   break
done

# name of *.cer expected from apple
if [[ $type ==  "production" ]]
then
    APS_CER_NAME="aps" # do not add .cer extension
	APPLE_API_PUSH="gateway.push.apple.com:2195"
else
    APS_CER_NAME="aps_development" # do not add .cer extension
	APPLE_API_PUSH="gateway.sandbox.push.apple.com:2195"
fi


echo "input app id: com.domain.appname"
read APP_ID


# temp config, do not touch
TEMP_FOLDER="temp"
TEMP_KEY_NAME="private"


echo ""
echo ""
echo "input PEM passphrase (minimum 4 chars long): ";
read PASSWORD


echo ""
echo ""
echo ""
echo "** [log]: creating private key & CertificateSigningRequest"
echo "** [log]: admin password is needed"

# CREATE CSR FILE (certSigningRequest) and private key, -subj arguments
# prevent prompted
sudo openssl req -new -newkey rsa:2048 -nodes -keyout "./$TEMP_FOLDER/$TEMP_KEY_NAME.key" -out "$TEMP_FOLDER/CSR.certSigningRequest" -subj /C=NL


# GO TO DEVELEOPER PORTAL AND CREATE CER FILE
RED='\033[0;31m'
NC='\033[0m' # No Color
echo -e "** [log]: go to ${RED}https://developer.apple.com/account${NC} and create the aps_development.cer or aps.cer for an app under Indentifiers/App IDs"
echo "** [log]: download  $APS_CER_NAME.cer in $TEMP_FOLDER/$APS_CER_NAME.cer"


# check 
while [ ! -e "$TEMP_FOLDER/$APS_CER_NAME.cer" ]
do
	echo ""
	echo "** [log]: $TEMP_FOLDER/$APS_CER_NAME.cer not found."
	echo ""
    read -p "Press enter to continue"
    echo ""
done

echo "** [log]: file [$APS_CER_NAME.cer] was found."

# not sure if conversion for certificaye and key is needed -> pem -> p12 -> pem
# needs furter testing to find out.

# convert certificate (.cer) to .pem
openssl x509 -in "$TEMP_FOLDER/$APS_CER_NAME.cer" -inform DER -outform PEM -out "$TEMP_FOLDER/$APS_CER_NAME.pem"
echo "** [log]: cretificate converted to pem"

# now convert certificate from pem to p12
openssl pkcs12 -export -out "$TEMP_FOLDER/$APS_CER_NAME.p12" -nokeys -in "$TEMP_FOLDER/$APS_CER_NAME.pem" -password pass:$PASSWORD
echo "** [log]: cretificate converted to p12"


# convertcertificate back to pem to include attributes in file..
openssl pkcs12 -nokeys -out "$TEMP_FOLDER/$APS_CER_NAME.pem" -in "$TEMP_FOLDER/$APS_CER_NAME.p12" -password pass:$PASSWORD
echo "** [log]: cretificate converted to pem final"


# key is already in pem format?? but has extension .key.. convert to pem anyway
openssl rsa -in "$TEMP_FOLDER/$TEMP_KEY_NAME.key" -out "$TEMP_FOLDER/$TEMP_KEY_NAME.pem" -outform PEM

echo "** [log]: converting key to pem"

# convert key to p12
openssl pkcs12 -export -out "$TEMP_FOLDER/$TEMP_KEY_NAME.p12" -nocerts -in "$TEMP_FOLDER/$TEMP_KEY_NAME.pem" -password pass:$PASSWORD

echo "** [log]: converting key to p12"

# convert key back to pem to include attributes in file?
openssl pkcs12 -nocerts -out "$TEMP_FOLDER/$TEMP_KEY_NAME.pem" -in "$TEMP_FOLDER/$TEMP_KEY_NAME.p12" -password pass:$PASSWORD

echo "** [log]: converting key to pem"


# combine certificate and key to final .pem file for use on server

cat "$TEMP_FOLDER/$APS_CER_NAME.pem" "$TEMP_FOLDER/$TEMP_KEY_NAME.pem" > certificates/$APP_ID.pem

echo "** [log]: final file [certificates/$APP_ID.pem] created"


echo "** [log]: verifying handshake cert <--> key"
# verify handshake pem with private key

openssl s_client -connect $APPLE_API_PUSH -cert "certificates/$APP_ID.pem" -key "$TEMP_FOLDER/$TEMP_KEY_NAME.pem"



echo "** [log]: cleaning temp files"
# clean temp folder..
#rm -rf temp/*






echo "** DONE **"