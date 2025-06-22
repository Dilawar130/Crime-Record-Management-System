Pakistani Crime Management - XAMPP Version
A comprehensive crime management designed for Pakistani law enforcement and citizens, featuring bilingual support (English/Urdu), CNIC-based authentication, and complete FIR management workflow.

🚀 Quick Setup for XAMPP
Prerequisites
XAMPP installed on your system
PHP 7.4 or higher
MySQL 5.7 or higher
Installation Steps
Download Files

Copy all project files to your XAMPP htdocs directory
Example: C:\xampp\htdocs\crime-management\
Start XAMPP Services

- Start Apache Web Server
- Start MySQL Database
Setup Database

Open phpMyAdmin: http://localhost/phpmyadmin
Import the database_setup.sql file
Database crime_management will be created automatically
Access the System

Visit: http://localhost/crime-management/
is ready to use!
🔐 Demo Login Credentials
Public Users (Citizens)
Username	Password	Name
ali.ahmad	password123	Ali Ahmad
fatima.sheikh	password123	Fatima Sheikh
hassan.malik	password123	Hassan Malik
ayesha.khan	password123	Ayesha Khan
Police Officers
Username	Password	Name	Rank
sp.ahmed	password123	SP Ahmed Ali Khan	SP
dsp.fatima	password123	DSP Fatima Nawaz	DSP
asi.hassan	password123	ASI Hassan Malik	ASI
const.usman	password123	Constable Usman Khan	Constable
admin	password123	Admin	Admin
🌟 Features
For Citizens (Public)
CNIC-based Registration - Register using Pakistani CNIC
Online FIR Filing - Submit detailed crime reports
Status Tracking - Track FIR progress with reference numbers
My FIRs Dashboard - View all filed reports
Bilingual Interface - English and Urdu support
For Police Officers
Role-based Access - Different access levels (IG, SP, DSP, ASI, Constable)
Case Management - Manage investigations and evidence
FIR Processing - Review and update FIR status
Dashboard Overview - Statistics and active cases
Technical Features
Responsive Design - Works on desktop, tablet, and mobile
Secure Authentication - Password hashing with PHP
Session Management - Secure user sessions
Pakistani Theme - Official colors (Green/Navy)
Mobile-First Design - Optimized for mobile devices
📁 File Structure
crime-management/
├── index.php              # Main homepage and dashboards
├── login.php              # User authentication
├── register.php           # Public user registration
├── fir-form.php           # FIR filing form
├── track-status.php       # FIR status tracking
├── my-firs.php           # User's FIR management
├── logout.php            # Session termination
├── database.php          # Database connection
├── database_setup.sql    # Complete database structure
├── styles.css            # Complete CSS styling
├── script.js             # JavaScript functionality
└── README.md             # This file
🗄️ Database Structure
Main Tables
location - Pakistani cities and areas
user - Police officers with hierarchy
police_station - Police stations across Pakistan
resident - Public users with CNIC
resident_credential - Public user login credentials
crime_category - Types of crimes (13 categories)
fir - First Information Reports
crime_information - Detailed crime data
Sample Data Included
10 Pakistani Locations (Karachi, Lahore, Islamabad, Rawalpindi)
13 Crime Categories (Theft, Assault, Fraud, etc.)
6 Police Officers with different ranks
6 Police Stations across major cities
5 Sample Citizens with CNIC data
🛡️ Security Features
Password Hashing - All passwords stored with PHP password_hash()
SQL Injection Protection - PDO prepared statements
Session Security - Secure session management
Input Validation - Client and server-side validation
CNIC Validation - Pakistani CNIC format validation
📱 Mobile Responsive
The is fully responsive and works perfectly on:

Desktop computers
Tablets
Mobile phones
All modern browsers
🌐 Browser Support
Chrome (recommended)
Firefox
Safari
Edge
Opera
🔧 Configuration
Database Configuration
Edit database.php if needed:

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "crime_management";
XAMPP Settings
Default XAMPP settings work out of the box. No additional configuration needed.

📊 Crime Categories
Theft (چوری) - Stealing of personal or public property
Assault (حملہ) - Physical attack on a person
Vandalism (توڑ پھوڑ) - Willful destruction of property
Fraud (دھوکہ دہی) - Financial crimes and deception
Burglary (چوری) - Breaking and entering with intent to steal
Drug Offense (منشیات) - Illegal drug related activities
Traffic Violation (ٹریفک خلاف ورزی) - Road and traffic related offenses
Domestic Violence (گھریلو تشدد) - Violence within domestic relationships
Cyber Crime (سائبر کرائم) - Internet and computer related crimes
Kidnapping (اغوا) - Unlawful abduction of persons
Murder (قتل) - Unlawful killing of a person
Robbery (ڈکیتی) - Theft involving force or threat of force
Other (دیگر) - Other criminal activities
🎨 Design Theme
Primary Color: Pakistan Green (#2D7D32)
Secondary Color: Pakistan Navy (#1565C0)
Accent Colors: Red (#D32F2F), Orange (#FF9800), Yellow (#FFC107)
Typography: Source Sans Pro, Roboto
Icons: Font Awesome 6.0
🚨 Emergency Contacts
Police Emergency: 15
General Inquiry: 1915
Email: info@police.gov.pk
📝 License
This project is designed for educational and demonstration purposes for Pakistani law enforcement agencies.

🤝 Support
For technical support or questions about the system:

Check the demo credentials above
Ensure XAMPP services are running
Verify database import was successful
Check PHP error logs in XAMPP
Status: ✅ Ready for Production Last Updated: 2024 Version: 1.0.0 Compatible with: XAMPP 3.2.4+, PHP 7.4+, MySQL 5.7+