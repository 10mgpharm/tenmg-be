<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://avatars.githubusercontent.com/u/178942928?v=4" width="50" alt="Consode Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About 10MG Project

10mg Pharm is a health tech e-commerce web application designed to facilitate the purchase of drugs and medications online. The platform will support a comprehensive end-to-end e-commerce process, including business onboarding, user and admin account management, product and order management, and a "Buy Now, Pay Later" (BNPL) credit facility. The BNPL facility, which includes credit scoring, voucher issuance, and repayment management, will provide customers with financial flexibility while ensuring secure and compliant transactions.:

## Setting up Project 

- Clone the project
- Install dependencies for laravel
```composer install``` or ```composer update```
- Install dependcies for frontend ship with the laravel default
```npm install```
- Start the project
```php artisan serve```

#### Creating a PR
- Switch to the main branch
- Checkout to a new feature branch using the ticket name assigned on JIRA
e.g if ticket name is TM-001 then that is the branch name
```git checkout -b TM-001```
- See [PULL_REQUEST_TEMPLATE.md](.github/PULL_REQUEST_TEMPLATE.md) for guide on filling the Pull request template
- If a feature branch exist for the current task you're working on, kindly branch out from that feature branch to create your branch
E.g if we have a feature/epic-name all related ticket that belongs to the epic, their branch should also point to it when submiting PR

#### Commit message
Follow the commit message standared as outline below:
```sh
PATTERN="^(feat|fix|docs|style|refactor|test|chore)(\([a-z]+\))?: .{1,100}$"
```
###### Commit Message flags
* feat - new feature or task implementation
* fix - bug fix
* docs - add update to readme or make some code documentation via comment only
* style
* refactor - minor rework on codebase or implementation improvement that affect one or less files (<= 5 files)
* test - adding test
* chore - major change to existing implementation that affect many files (>=6 files)


e.g 
```
git commit -m 'feat: implement login flow'
```


## 10MG Backend Engrs Team Agreement
This section is dedicated for backend engineers to align with the codebase team agreement as we prepare to contribute to the 10mg project.

#### Technology Stack and Standard Packages
- Laravel 11
- Php 8 syntax
- Pest for unit test 

Laravel is accessible, powerful, and provides tools required for large, robust applications.

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

#### Standard Packages
- spatie/laravel-permission - For managing roles and permission https://spatie.be/docs/laravel-permission/v6/installation-laravel
- meatwebsit/excel - For excel export and import https://docs.laravel-excel.com/3.1/getting-started/installation.html
- spatie/laravel-activitylog - For logging key user actions for audit log https://spatie.be/docs/laravel-activitylog/v4/introduction
- intervention/images - For image manipulations https://image.intervention.io/v2



## Codebase Agreement

#### Route registration
The app uses the latest laravel with some little change, 
All route will be under folder /routes/api.php for v1
Future versions will be under /routes/api-version_number.php  e.g /routes/api-v2.php 
Use standard rest api verbs for route
Do not use laravel built-in api resource route convention

#### API Request handling
Always use FormRequest object to define and validate request for controller methods
Always use Resource object to return response back 
All Controller should only have their individual Service class or Generic Service class
For example SignupController, LoginController, OtpController can use same Generic service class call AuthService
While UserController can use UserService class

#### Service class 
All Service class should have their Interface class e.g IUserService
All service class should implement their respective interface methods e.g Class UserService implements IUserService {….}
Bind all Service class with their respective Interface on the Service container
All Service class should have the Repository class they interact with loaded from their constructor
Service calls should not query database directly but can utilise response from the repository class method to perform business logic
Service class should focus on performing business logic only, send email, dispatch jobs where necessary 

#### Emails Handling
Create Email Notifications class for all email 
Naming convention should be Description and Notification as suffix e.g LoginSuccessfulNotification
this indicate Login successful email notification
Create Event and Event Listener to handle sending email, this means all email should be dispatched with event
e.g event(new LoginSuccessEvent(user: $user);
All Email Notifications class should have the mail and database channel enabled

#### Environment Variable and Loading App Configuration
Do not load env values directly in business logic e.g $url = env(“APP_URL”)  - not allowed
Load from config object instead e.g $url = config(“app.url”)
If you’re working on a task that introduce new environment variable
Ensure you add a sample to .env.example

> Permission and Access control is should be handled using the laravel spatie package


## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License
The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Documentation Reference
https://laravel.com/docs/11.x/passport#managing-clients

https://laravel.com/docs/11.x/middleware#global-middleware
