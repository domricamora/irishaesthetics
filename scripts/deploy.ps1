<#
    Deploys the current commit to the live clinic site.

    Run it from the project root:

        pwsh -File scripts/deploy.ps1

    Why this is a script and not a habit: the build bakes the APP_URL into
    every link the frontend emits, because the bundler regenerates the
    Wayfinder route files from it. The local APP_URL is

        http://localhost/aesthetic/public

    and the live one is

        https://irish.deskpulse.click

    A plain `npm run build` therefore produces a bundle whose every nav link
    points at /aesthetic/public/... and the whole site 404s on click, while
    typing a URL by hand still works. That is the exact failure this script
    exists to prevent.

    The host has no node and no composer, so the frontend is built here and
    uploaded, and migrations run over there.
#>
[CmdletBinding()]
param(
    [string]$Host_ = 'htrjymuo@ck.deskpulse.click',
    [int]$Port = 9022,
    [string]$Remote = '~/public_html/irish.deskpulse.click',
    [string]$AppUrl = 'https://irish.deskpulse.click',
    # The database that folder is expected to use. The cPanel account grants one
    # database per site and the name carries the site slug, so this catches a
    # folder that looks right while its .env points somewhere else. Set
    # -AllowDatabaseMismatch only when the database is genuinely named
    # something else.
    [string]$ExpectedDatabase = '',
    [switch]$AllowDatabaseMismatch,
    # OpenSSH refuses a key that anyone but you can read. If yours trips that
    # on Windows, point at a copy with clean permissions.
    [string]$IdentityFile = ''
)

$ErrorActionPreference = 'Stop'
Set-Location (Join-Path $PSScriptRoot '..')

# Which site is this? Read it from the repository rather than trusting the
# command line.
#
# This repository was forked from the Patrice clinic codebase, and that fork
# originally shipped with this script still pointing at
# ~/public_html/patrice.deskpulse.click. Running it here published the Irish
# build over the Patrice site and applied this repository's rebrand migration
# to the Patrice database. Defaults alone did not stop it, so the target is
# now checked against the slug in config/clinic.php -- the one value that
# cannot be wrong about which brand this repository is -- and a mismatch
# stops the deploy before anything is uploaded.
$slug = (Select-String -Path config\clinic.php `
    -Pattern "'organization' => env\('CLINIC_ORGANIZATION', '([^']+)'\)"
).Matches[0].Groups[1].Value

if (-not $slug) { throw 'Could not read the organization slug from config/clinic.php.' }

$expected = "https://$slug.deskpulse.click"

Write-Host "This repository is the '$slug' site; it may only deploy to $expected"

if ($AppUrl.TrimEnd('/') -ne $expected) {
    throw @"
Refusing to deploy. This is the '$slug' repository, so the live URL must be
    $expected
but the target is
    $AppUrl

Publishing this build to a different site overwrites that site's files and
runs this repository's migrations against that site's database. If you mean
to deploy a different clinic, open that project's own repository and run its
deploy script.
"@
}

if ($Remote.TrimEnd('/') -notlike "*/public_html/$slug.deskpulse.click") {
    throw @"
Refusing to deploy. This is the '$slug' repository, so the remote folder must
be
    ~/public_html/$slug.deskpulse.click
but the target is
    $Remote
"@
}

$Ssh = @('-F', 'none', '-p', $Port)
if ($IdentityFile) { $Ssh += @('-i', $IdentityFile) }

function Invoke-Remote([string]$Command) {
    & ssh @Ssh $Host_ $Command
    if ($LASTEXITCODE -ne 0) { throw "remote command failed: $Command" }
}

# The two sites run on one account but on separate databases, so the folder is
# only half the address: the .env inside it names the database that this
# deploy's `php artisan migrate --force` will actually run against. Checking
# it here, before a single file is uploaded, is what stops this repository
# from migrating the sibling site's data even if the folder name is right.
Write-Host "Confirming $Remote is configured against the '$slug' database..."
# The quotes around the value are stripped here rather than on the far end.
# Nesting a quote character inside an ssh command string is what broke this
# the first time; trimming it in PowerShell has no quoting to get wrong.
$remoteDbRaw = & ssh @Ssh $Host_ "sed -n 's/^DB_DATABASE=//p' $Remote/.env | head -1"
$remoteDb = ([string]($remoteDbRaw | Out-String)).Trim().Trim('"').Trim("'")

if ($LASTEXITCODE -ne 0 -or -not $remoteDb) {
    throw "Could not read DB_DATABASE from $Remote/.env. Refusing to deploy blind."
}

Write-Host "  remote DB_DATABASE = $remoteDb"

if (-not $AllowDatabaseMismatch) {
    $wanted = if ($ExpectedDatabase) { $ExpectedDatabase } else { "htrjymuo_$slug" }

    if ($remoteDb -ne $wanted) {
        throw @"
Refusing to deploy. The remote folder's .env points at the database
    $remoteDb
but this is the '$slug' site, which should use
    $wanted

`php artisan migrate --force` would apply this repository's migrations to
that other database. If '$remoteDb' really is this site's database, pass
-ExpectedDatabase $remoteDb to say so explicitly.
"@
    }
} elseif ($ExpectedDatabase -and $remoteDb -ne $ExpectedDatabase) {
    throw "Expected $ExpectedDatabase but the remote .env names $remoteDb."
}

Write-Host 'Checking the tree is clean and the suite passes...'
if (git status --porcelain) { throw 'Uncommitted changes. Commit before deploying.' }
php artisan test

Write-Host "Building the frontend for $AppUrl ..."
$previous = $env:APP_URL
$env:APP_URL = $AppUrl
try {
    npm run build
} finally {
    $env:APP_URL = $previous
}

# The bundler regenerates these from APP_URL; a leftover local path here is
# the bug this script exists to catch.
$stale = Get-ChildItem public\build\assets\*.js |
    Select-String -Pattern '/aesthetic/public' -List
if ($stale) {
    throw "The build still contains /aesthetic/public in $($stale.Count) file(s)."
}

# LastIndexOf, rather than Split-Path (which hands back a backslash, and bash
# will not expand a tilde that has one) or -split (whose precedence is easy
# to get wrong). The last slash is the only one that matters here.
$cut = $Remote.LastIndexOf('/')
$remoteParent = $Remote.Substring(0, $cut)
$remoteLeaf = $Remote.Substring($cut + 1)

$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
# The slug is in the backup names because this account holds several sites and
# a dump called db-<timestamp>.sql cannot be told apart from another site's.
# The one that gets restored after an incident is the one you most need to
# identify at a glance.
Write-Host 'Backing up the live site and database...'
Invoke-Remote "cd $remoteParent && tar czf ~/backups/site-$slug-$stamp.tar.gz $remoteLeaf 2>/dev/null"
Invoke-Remote "cd $Remote && DB=`$(grep -E '^DB_' .env | sed 's/^export //') && eval `"`$DB`" && mysqldump --single-transaction --quick -h `"`${DB_HOST:-localhost}`" -u `"`$DB_USERNAME`" -p`"`$DB_PASSWORD`" `"`$DB_DATABASE`" > ~/backups/db-$slug-$stamp.sql 2>/dev/null"

# A dump that is empty means the mysqldump failed and the deploy would then
# overwrite the files with no way back. Better to stop here than to discover it
# during an incident.
$dumpSize = (& ssh @Ssh $Host_ "wc -c < ~/backups/db-$slug-$stamp.sql").Trim()
if ($dumpSize -lt 1024) {
    throw "The database backup is only $dumpSize bytes. Refusing to deploy with no usable restore point."
}
Write-Host "  site-$slug-$stamp.tar.gz and db-$slug-$stamp.sql ($dumpSize bytes)"

Write-Host 'Uploading source...'
git archive --format=tar HEAD | & ssh @Ssh $Host_ "cd $Remote && tar xf -"

Write-Host 'Uploading the built frontend...'
tar cf - -C public build | & ssh @Ssh $Host_ "cd $Remote/public && rm -rf build && tar xf -"

Write-Host 'Migrating, clearing every cache, then rebuilding them...'
Invoke-Remote "cd $Remote && php artisan migrate --force"

# optimize:clear is the whole set -- config, route, view, event, compiled and
# anything else registered as a cache -- rather than naming three of them and
# trusting the list to stay complete. A cache left behind after an upload is
# the same failure as a stale browser: the site keeps serving the old thing and
# nothing in the logs says why.
Invoke-Remote "cd $Remote && php artisan optimize:clear"

# Clearing without rebuilding leaves the site answering from nothing, which is
# slower for every visitor until something else happens to warm it. Rebuild the
# caches that can be rebuilt, so a deploy ends with the site in the state it
# should actually be running in.
Invoke-Remote "cd $Remote && php artisan config:cache"

# route:cache refuses to run when any route is a closure. That costs
# performance, not correctness, so it is reported rather than thrown -- a
# deploy script that fails over an optimisation is a deploy script people
# start skipping.
& ssh @Ssh $Host_ "cd $Remote && php artisan route:cache"
if ($LASTEXITCODE -ne 0) {
    Write-Warning 'route:cache was refused (a closure route). The site is fine, just without a cached route table.'
}

Invoke-Remote "cd $Remote && mkdir -p public/media/photos/staff public/media/photos/products && chmod 775 public/media/photos/staff public/media/photos/products"

# Prove the caches were rebuilt rather than assuming it happened.
$cached = & ssh @Ssh $Host_ "cd $Remote && ls bootstrap/cache/config.php 2>/dev/null | wc -l"
if ($cached.Trim() -ne '1') {
    throw 'config:cache did not produce bootstrap/cache/config.php -- the site is running uncached.'
}
Write-Host '  caches cleared and rebuilt'

Write-Host 'Verifying...'
foreach ($path in @('/', '/about', '/journal', '/book')) {
    $code = & ssh @Ssh $Host_ "curl -s -o /dev/null -w '%{http_code}' $AppUrl$path"
    if ($code -ne '200') { throw "$path returned $code" }
    Write-Host "  $path -> $code"
}

# Put the local links back so the development site still works.
php artisan wayfinder:generate --with-form | Out-Null
Write-Host "Deployed. Backups: ~/backups/site-$slug-$stamp.tar.gz and db-$slug-$stamp.sql"
