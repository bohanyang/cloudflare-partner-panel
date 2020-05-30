#!/usr/bin/env sh

set -eux

_ZipFolderContents() {
    rm -f "$1"
    7z a "$1" "$2/."
}

_Kudu_PutZip() {
    curl -f -u "$_kudu_login" -X PUT --data-binary @"$1" "https://$_kudu_app.scm.azurewebsites.net/api/zip$2"
}

_Kudu_Delete() {
    curl -f -u "$_kudu_login" -X DELETE -H 'If-Match: *' "https://$_kudu_app.scm.azurewebsites.net/api/vfs$1?recursive=true" || true
}

_Kudu_PutFolder() {
    _ZipFolderContents "$1.zip" "$1"
    _Kudu_Delete "$2"
    _Kudu_PutZip "$1.zip" "$2"
}

_RecursivePull() {
    _src="$1"; _dest="$2"; shift 2
    for f in "$@"; do
        rm -rf "${_dest:?}/$f"
        cp -R "$_src/$f" "$_dest/$f"
    done
}

_Clean() {
    _from="$1"; shift
    for f in "$@"; do
        rm -rf "${_from:?}/$f"
    done
}

_BuildCloudflarePartnerPanel() {
    mkdir -p "$1"
    _RecursivePull "$2" "$1" actions assets languages cloudflare.class.php config.php index.php settings.php composer.json composer.lock
    composer install -d "$1" --no-dev --optimize-autoloader
    _Clean "$1" composer.json composer.lock
}

echo "$_config_php" > config.php
_temp="$(mktemp -d)"
_BuildCloudflarePartnerPanel "$_temp" .
_Kudu_PutFolder "$_temp" /site/wwwroot/
rm -r "$_temp"
az webapp restart --resource-group "$_azure_resource_group" --name "$_kudu_app"
