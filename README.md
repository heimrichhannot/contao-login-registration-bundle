# Contao Login Registration Bundle

This bundle provide a login frontend module allowing direct register the user if user not exist.

## Usage

### Install

Install with contao manager or with composer: 

```bash
composer require heimrichhannot/contao-login-registration-bundle
```

Update the database.

### Setup

1. Create a frontend module of type "Login Registration".
2. Add the module to a page.
3. For ease of usage, it is recommended to only allow email addresses as username. 
   You could adjust your dca accordingly or use a bundle to provide such functionality. 
   Otherwise, you need to implement a listener for `PrepareNewMemberDataEvent` to provide a user email address.

## Developers

### PHP Events

| Event                     | Description                                                                      |
|---------------------------|----------------------------------------------------------------------------------|
| AdjustUsernameEvent       | Manipulate the username before checking for existing user or creating a new one. |
| PrepareNewMemberDataEvent | Adjust the new member data before the member is created.                         |



