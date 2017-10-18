<p align="center">Deploy with GitHub and pure PHP. No git on server or ftp needed.</p>

## Usage

1. Install with: `composer require godric/github-deployment dev-master`
2. Create deployment script, for example `deploy.php` with following contents:

```php
<?php // deploy.php in website root

require 'vendor/autoload.php';

(new Godric\GithubDeployment\GithubDeployment([
    'secret'    =>  'your_secret',  // pick your secret
    'target'    =>  __DIR__ . '/.', // directory beeing synchronized – same as deploy.php
]))->autorun();
```

3. Upload new files to your server.
4. Go to: (your github repo) > settings > webhooks > add webhook
5. Set url to `http://yoursite.com/deploy.php` and secret to `your_secret`.
6. Done! From now on, all files added/modified/removed in new commits to `master` will appear on your server.

## Features

- No need for git or `system()` calls on remote server – should work well with shared hosts.
- Works with pushes to master as well as pull-request merging.
- Multiple commits in one push are also OK.
- Current contents of remote directory are not modified – only changes in commits are applied.
- Automatic `composer install` on remote before updating files.
- TODO: custom _before_ or _after_ scripts (for example DB migrations).

## Requirements

- Write access to target directory.

## Notes

### Test scenarios TBD

- new file in non-existent directory
- exotic directory names
