#!/usr/bin/with-contenv bash
set -eu

config_root="/config/dokuwiki"
conf_dir="$config_root/conf"
plugins_dir="$config_root/lib/plugins"
plugin_dir="$config_root/lib/plugins/dw2pdf"
bpmnio_source="/workspace-plugins/bpmnio"
core_plugins="acl authplain config extension usermanager"
dw2pdf_tag="2026-01-08"
dw2pdf_commit="de2f4b75805e2d7f89a0526a4c835b03a9e875aa"
dw2pdf_ref_file="$plugin_dir/.pinned-ref"

mkdir -p "$conf_dir" "$plugins_dir"

if [ ! -L "$plugins_dir/bpmnio" ] || [ "$(readlink "$plugins_dir/bpmnio" 2>/dev/null || true)" != "$bpmnio_source" ]; then
    rm -rf "$plugins_dir/bpmnio"
    ln -s "$bpmnio_source" "$plugins_dir/bpmnio"
fi

if [ ! -f "$plugins_dir/authplain/plugin.info.txt" ]; then
    version="$(awk '{print $1}' /app/www/public/VERSION)"
    tmp_dir="$(mktemp -d)"
    archive_path="$tmp_dir/dokuwiki.tgz"

    wget -q -O "$archive_path" "https://download.dokuwiki.org/src/dokuwiki/dokuwiki-${version}.tgz"
    tar -xzf "$archive_path" -C "$tmp_dir"

    source_root="$(find "$tmp_dir" -maxdepth 1 -type d -name 'dokuwiki-*' | head -n 1)"

    for plugin in $core_plugins; do
        rm -rf "$plugins_dir/$plugin"
        cp -R "$source_root/lib/plugins/$plugin" "$plugins_dir/$plugin"
    done

    rm -rf "$tmp_dir"
fi

cat > "$conf_dir/local.php" <<'PHP'
<?php
$conf['title'] = 'BPMN.io Test Wiki';
$conf['useacl'] = 1;
$conf['authtype'] = 'authplain';
$conf['superuser'] = '@admin';
$conf['manager'] = '@admin';
PHP

cat > "$conf_dir/acl.auth.php" <<'EOF_ACL'
# acl.auth.php
# <?php exit()?>
*               @ALL          0
*               @user         1
*               @admin       16
EOF_ACL

cat > "$conf_dir/users.auth.php" <<'EOF_USERS'
# users.auth.php
# <?php exit()?>
# login:passwordhash:Real Name:email:groups,comma,separated
user:$2y$12$9/wuGFQgZj0BaUwWjs4jH.tdESi3s5fDTPujQ6UuIUDAYz206/L5S:Normal User:user@example.invalid:user
admin:$2y$12$gSv4KDCe8sGZco/CTLK.herdjvFHAYOqriXVLNiUnhjEL5s/W//02:Admin User:admin@example.invalid:admin,user
EOF_USERS

if [ ! -f "$dw2pdf_ref_file" ] || [ "$(cat "$dw2pdf_ref_file" 2>/dev/null || true)" != "$dw2pdf_commit" ]; then
    tmp_dir="$(mktemp -d)"
    archive_path="$tmp_dir/dw2pdf.tar.gz"

    wget -q -O "$archive_path" "https://github.com/splitbrain/dokuwiki-plugin-dw2pdf/archive/${dw2pdf_commit}.tar.gz"
    tar -xzf "$archive_path" -C "$tmp_dir"

    source_root="$(find "$tmp_dir" -maxdepth 1 -type d -name 'dokuwiki-plugin-dw2pdf-*' | head -n 1)"

    rm -rf "$plugin_dir"
    mv "$source_root" "$plugin_dir"
    printf '%s\n' "$dw2pdf_commit" > "$dw2pdf_ref_file"

    rm -rf "$tmp_dir"
fi

if [ -f /app/www/public/install.php ]; then
    rm -f /app/www/public/install.php
fi

chown -R abc:abc "$conf_dir" "$plugin_dir"

for plugin in $core_plugins; do
    chown -R abc:abc "$plugins_dir/$plugin"
done

echo "[custom-init] Dokuwiki test bootstrap ready"
