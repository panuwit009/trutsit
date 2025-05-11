# Thesis Search System

This project is a Thesis Search System built with PHP, MySQL, Composer, and other technologies. The following steps will guide you to set up and run the project on your local machine.

## Installation Instructions

### 1. Install PHP Software on Your Machine
We recommend using **AppServ version 9.3.0** as it is easy to install and works right out of the box.
- You can download AppServ from the [AppServ website](https://www.appserv.org/th//).

### 2. Clone the Project to Your Local Machine
After installing AppServ, go to the directory where you installed AppServ. Inside the **`www`** folder, clone this project into it:
  ```bash
  git clone https://github.com/panuwit009/trutsit.git
```

### 3. Install Composer
If you don't have Composer installed yet, you can download it from the Composer website.
After installing Composer, go to the project directory you just cloned and run the following command to install the dependencies:
 ```bash
composer install
```

### 4. Set Up the MySQL Database
Go to phpMyAdmin

Log in using the username and password you set up when you installed AppServ

Click on New to create a new database

Enter a database name in the Database name field and click Create

After creating the database, select it, and then click on the Import tab

Choose the thesissearchsystem.sql file from the project folder you cloned and click Go to import the database structure and sample data

### 5. Configure Database Connection in config.php
Open the config.php file located in the project folder

Update the following configuration with the details from your AppServ MySQL installation:

```php
$host = 'localhost';
$dbname = 'your-database-name';
$username = 'your-mysql-username';
$password = 'your-mysql-password';
```

### 6. Access the System via Web Browser
Once everything is set up, you can access the system by navigating to the following URL in your browser: http://localhost/(your-project-folder-name)

### Summary
After completing the steps above, you should be able to access the system via the URL mentioned earlier and start using the project on your local machine
