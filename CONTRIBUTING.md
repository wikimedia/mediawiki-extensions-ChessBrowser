# ChessBrowser
By contributing to this project you agree to release your contributions under
the terms of the GNU General Public License v3 or any later version of
that license. You additionally agree to follow the [Code of Conduct for MediaWiki technical spaces](https://www.mediawiki.org/wiki/Code_of_Conduct).

## Setting up a development environment
To get started with a MediaWiki development environment, follow the instructions
at [MediaWiki-Vagrant#Quick start](https://www.mediawiki.org/wiki/MediaWiki-Vagrant#Quick_start).

Once you have set up your MediaWiki environment, navigate to your extensions
directory and clone the repository using

```
git clone ssh://gerrit.wikimedia.org:29418/mediawiki/extensions/ChessBrowser
```

Finally, in your LocalSettings.php file, add `wfLoadExtension( 'ChessBrowser' );`
