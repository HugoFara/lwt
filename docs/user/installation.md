# LWT Installation

* *Last update*: February 20, 2023

Let's install the LWT server. LWT uses a client-server architecture, which means it
will run in your browser as a classical website. You can use any computer as the
server, here are some ways to do it.

## A bird's-eye view

Whatever installation you choose, the steps will look like the following:

1. Set-up a server with a database system.
2. Download [LWT](https://github.com/HugoFara/lwt/releases).
3. Create ``connect.inc.php`` file with your password.
4. Start the server and ready to go!

There are two main ways to install LWT: on your computer or using [containers](#run-in-a-docker-container). We recommend the first solution as the most straightforward. The second solution has a simpler installation method, but takes a lot of storage.

## Windows 10/11

Two main softwares can be used to set up a local server on your computer: XAMPP and EasyPHP. We recommand XAMPP because it supports higher PHP version, but feel free to use any softare you like.

### Using XAMPP (recommended)

1. Install XAMPP
   1. Go to <https://www.apachefriends.org/download.html>
   2. Download "XAMPP for **Windows**". PHP starting from **8.0** is supported.
   3. Open your Downloads folder and run the downloaded "xampp-windows-x64-xxx-installer.exe". Please install the components Apache, MySQL, PHP and phpMyAdmin into the folder C:\xampp.

2. Get the [latest GitHub release](https://github.com/HugoFara/lwt/releases), unzip it.

   You can also try to download the [latest stable version](https://github.com/HugoFara/lwt/archive/refs/heads/master.zip) if you want the cutting-edge updates (that may include some bugs)

3. Now go into "C:\xampp\htdocs\lwt". Rename the file "connect_xampp.inc.php" to "connect.inc.php". Sometimes the "php" extension is hidden, so be careful! You can display file extensions via the Windows Explorer settings and check it.

4. Start LWT server
   1. Start the "XAMPP Control Panel" ("C:\xampp\xampp-control.exe") and start the two modules Apache and MySQL. Now the two module names should have a green background color.
   2. LWT can now be started. Open a browser, and open <http://localhost/lwt> (please bookmark).

5. You may now define the first language you want to learn or install the LWT demo database.

If you start up Windows, you must repeat steps 4 and 5.

If you want to start "XAMPP Control Panel" every time you start Windows and to avoid Step 4.1, put a "XAMPP Control Panel" link to "C:\xampp\xampp-control.exe" into "C:\Users\(YourUID)\AppData\Roaming\Microsoft\Windows\Start Menu\Programs\Startup". To autostart also the Apache and MySQL modules, please open "Config" within the XAMPP Control Panel and check the two checkboxes.

> Hint: To fix a "XAMPP Control Panel" error "Xampp-control.ini Access is denied", please read and do the instructions in <https://www.codermen.com/fix-xampp-server-error-xampp-control-ini-access-is-denied/>

Now you must only do step 4.2 to start LWT.

### Using EasyPHP

1. Get Visual C++
   1. Download "vcredist_x86.exe" from <https://www.microsoft.com/en-us/download/details.aspx?id=30679>
   2. Choose the x86 version and download.
   3. Run the installer "vcredist_x86.exe" in the Downloads folder.

2. Get EasyPHP
   1. Go to <https://www.easyphp.org/easyphp-devserver.php>
   2. Download "EasyPHP DevServer 17.0".
   3. Open your Downloads folder and run the downloaded "EasyPHP-Devserver-17.0-setup.exe".
   4. Install into "C:\Program Files (x86)\EasyPHP-Devserver-17".

3. Get the [latest GitHub release](https://github.com/HugoFara/lwt/releases), unzip it.

   You can also try to download the [latest stable version](https://github.com/HugoFara/lwt/archive/refs/heads/master.zip) if you want the cutting-edge updates (that may include some bugs)

4. Install everything
   1. Go to "C:\Program Files (x86)\EasyPHP-Devserver-17\eds-www\lwt".
   2. Rename the file "connect_easyphp.inc.php" to "connect.inc.php". Sometimes the "php" extension is hidden, so be careful! You can display file extensions via the Windows Explorer settings and check it.

5. Start EasyPHP
   1. Start EasyPHP via Desktop Icon (Devserver 17). In the Task Bar near the clock appears the EasyPHP app icon (it may be hidden!).
   2. LWT can now be started. Right-Click on the EasyPHP icon in the taskbar, choose "Servers->Start/Restart all Servers", open a browser, and open <http://127.0.0.1/lwt> (please bookmark).

6. You may now define the first language you want to learn or install the LWT demo database.

If you start up EasyPHP, you must repeat step 5.1 and 5.2.

If you want to start EasyPHP every time you start Windows and avoid step 5.1, put an EasyPHP link into "C:\Users\(YourUID)\AppData\Roaming\Microsoft\Windows\Start Menu\Programs\Startup".

Now you must only do step 5.2 to start LWT.

## macOS 10.10+
>
> This section may be obsolete! Your help is welcome!

1. Go to <https://www.mamp.info/en/downloads/>

2. Download "MAMP & MAMP PRO" (currently version 6.6).

3. Double-click on the downloaded installation package "MAMP_MAMP_PRO_xxx.pkg", accept the license, click on "Install for all users..." and on "Continue", on the next panel titled "Standard Install on Macintosh HD" click on "Customize", deselect "MAMP PRO", and click Install. You must enter your password. After this step MAMP is installed within a folder named "MAMP" in the Applications folder.

4. Get the [latest GitHub release](https://github.com/HugoFara/lwt/releases), unzip it.

   You can also try to download the [latest stable version](https://github.com/HugoFara/lwt/archive/refs/heads/master.zip) if you want the cutting-edge updates (that may include some bugs)

5. Go to ``/Applications/MAMP/htdocs/lwt``. Rename the file ``connect_mamp.inc.php`` to ``connect.inc.php``.

6. Open ``MAMP.app`` in ``/Applications/MAMP``. Accept the messages from the firewall. Apache and MySQL start automatically.

7. LWT can now be started in your web browser, go to: <http://localhost:8888/lwt>.

8. You may define the first language you want to learn or install the LWT demo database.

If you want to use LWT again, just do steps 6 and 7.
The local webserver (MAMP) will be automatically stopped by quitting the MAMP application.

## Linux

### Using the Linux Installer

We provide an installer that runs the commands described in the next section. To use the installer:

1. Download the [latest GitHub release](https://github.com/HugoFara/lwt/releases/latest), unzip it.
2. Open a terminal in the downloaded folder, enable execution with ``chmod +x ./INSTALL.sh``.
3. Run the script with ``./INSTALL.sh``.
4. You can start using LWT at <http://localhost/lwt>

### Installing on Linux by hand

The following instruction were tested on Raspbian Stretch.

1. Open a terminal, type and execute the following commands:

   1. Installation of LAMP:

      ```bash
      sudo apt-get update
      sudo apt-get install apache2 libapache2-mod-php php php-mbstring php-mysql php-xml mariadb-server
      ```

      Note: you should be able to freely switch between MySQL and MariaDB.

   2. Check if everything is okay:
      * ``php -v`` should show a PHP version equal or above to 8.0.0.
      * <http://locahlhost> should display a nice web page.
      * ``mysql -V`` should work.

   3. Enable the extensions
      1. Go to your PHP folder (``/etc/php/{{desired PHP version}}/{{PHP type}}/``)
      2. Run ``sudo nano php.ini``.
      3. Delete the ";" symbols before ``extension=mbstring`` and ``extension=mysqli``.

   4. Set MySQL root Password to "abcxyz"

      ```bash
      sudo mysql
      ```

      Then type

      ```MySQL
      ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'abcxyz';
      FLUSH privileges;
      QUIT; 
      ```

   5. (Optionnal) Check MySQL access

       ```bash
       mysql -u root -p
       abcxyz
       ```

       If you see the MySQL prompt ``mysql>`` after the first command, everything is OK. Quit with

       ```MySQL
       QUIT;
       ```

2. Get the [latest GitHub release](https://github.com/HugoFara/lwt/releases).

   You can also try to download the [latest stable version](https://github.com/HugoFara/lwt/archive/refs/heads/master.zip) if you want the cutting-edge updates (that may include some bugs)
  
3. Unzip it.

4. Rename the file ``connect_xampp.inc.php`` (in the unzipped folder) to ``connect.inc.php``.

5. Edit ``connect.inc.php`` and set the MySQL password in line
``$passwd = "";``. Change it to ``$passwd = "abcxyz";``. Save the edited file connect.inc.php.

6. Open a terminal, type and execute the following commands:

   ```bash
   sudo rm /var/www/html/index.html
   sudo mv /[... Path to downloaded LWT ...]/lwt /var/www/html
   sudo chmod -R 755 /var/www/html/lwt
   sudo service apache2 restart
   sudo service mysql restart
   ```

7. LWT can now be started in your web browser, go to: <http://localhost/lwt>.

8. You may install the LWT demo database, or define the first language you want to learn.

If you want to use LWT again, just do step 7.

## Run in a Docker container

[Docker](https://docs.docker.com/get-docker/) is the easiest way to install LWT,
but it will use more or less 1 GB on your system.

### Using the installer

For an light-weight installer, you may use
[HugoFara/lwt-docker-installer](https://github.com/HugoFara/lwt-docker-installer).

### Build image from source

Download the lwt, open a terminal inside it, then type

```bash
docker compose up -d
```

By default the server can be accessed on port 8010 (<http://localhost:8010/lwt>).

To remove the created containers run

```bash
docker compose down
```

## Dependency management with Composer

If you have a technical knowledge of how Composer works for dependency management, you may consider using
Composer. It is *required for contributors only*, but advanced users may want to use it as well.
The official repository is at <https://packagist.org/packages/hugofara/lwt>.

## Upgrade LWT

1. Backup the LWT directory. Backup your database (within LWT).

2. Get the [latest GitHub release](https://github.com/HugoFara/lwt/releases).

   You can also try to download the [latest stable version](https://github.com/HugoFara/lwt/archive/refs/heads/master.zip) if you want the cutting-edge updates (that may include some bugs)

3. Unzip it.

4. Copy the following (if not already at its place and OK) from your LWT backup into the LWT directory: "connect.inc.php" and the whole "media" sub-directory (if you created one; contains your MP3 audio files).

5. Clear the web browser cache and open LWT as usual.

## Something Went Wrong

Need more help? You can contact us through  [GitHub](https://github.com/HugoFara/lwt/issues) and [Discord](https://discord.gg/xrkRZR2jtt)!

You can also consult the troubleshooting section of the documentation at <https://hugofara.github.io/lwt/docs/info.html#faq>.

Please note that *PHP below version 8.0 is no longer supported*.
