# Cloudmesa

A web-based desktop with a basic sign-in system, web applications, and a simple GUI.

- [x] list all files & folders in "data" directory
- [x] ability to cut/copy/paste files
- [x] ability to cut/copy/paste folders
- [x] ability to upload files
- [ ] ability to upload folders
- [x] drag and drop to upload
- [x] file & folder icons
- [x] ability to customize background
- [x] basic sign-in system
- [x] mobile interface
- [x] ability to set write access for users
- [x] ability to extract archives
- [x] ability to install/configure web applications

# Requirements

This project requires the PHP PDO SQLite extension to be installed (see [here](https://www.php.net/manual/en/pdo.installation.php) for instructions).
For web apps to work properly, fill in the "url" in ```js/programs.json``` with the URL where Cloudmesa will be hosted (ex: "http://mysite.com/").
If you want to enable archive extraction, "p7zip-full" must be installed.

If you want to use [IodineGBA](https://github.com/taisel/IodineGBA), you must place a GBA BIOS file ("gba_bios.bin") into the apps/IodineGBA folder.

Cloudmesa is being tested on a Raspberry Pi Model 3 B+; it can operate on low-end hardware (1GB RAM, 1.5GHz CPU) quite well.
