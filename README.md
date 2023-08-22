# This is my package laravel-github-copilot-chat

[![Latest Version on Packagist](https://img.shields.io/packagist/v/elsayed85/laravel-github-copilot-chat.svg?style=flat-square)](https://packagist.org/packages/elsayed85/laravel-github-copilot-chat)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/elsayed85/laravel-github-copilot-chat/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/elsayed85/laravel-github-copilot-chat/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/elsayed85/laravel-github-copilot-chat/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/elsayed85/laravel-github-copilot-chat/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/elsayed85/laravel-github-copilot-chat.svg?style=flat-square)](https://packagist.org/packages/elsayed85/laravel-github-copilot-chat)

Chat With Github Copilot inside Command Line using Laravel.

## Installation

You can install the package via composer:

```bash
composer require elsayed85/laravel-github-copilot-chat
```


You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-github-copilot-chat-config"
```

This is the contents of the published config file:

```php
return [
    'stream' => true,
    'intent' => false,
    'model' => 'copilot-chat',
    'temperature' => 0.1,
    'top_p' => 1,
    'n' => 1,

    'client_id' => '01ab8ac9400c4e429b23', // Don't change this
    'user_agent' => 'GithubCopilot/3.99.99', // Don't change this
];
```
## Usage

## [Github Copilot Chat](https://marketplace.visualstudio.com/items?itemName=GitHub.copilot-chat)

Run : 
```bash
php artisan copilot:chat
```

for First Time You need to login to your github account and copy the code from the terminal and auth with github

``` bash
Got To https://github.com/login/device/ and enter the code: B720-C162
```

then after auth you need to confirm that 

``` bash
    ┌ Do you entered the code successfully? ───────────────────────┐
        Yes / No 
    └──────────────────────────────────────────────────────────────┘
```

## Github Copilot CLI
### [NodeJs Package](https://www.npmjs.com/package/@githubnext/github-copilot-cli)

To use github-copilot-cli 

A CLI experience for letting GitHub Copilot help you on the command line.

GitHub Copilot CLI translates natural language into shell commands, with modes for different domains. After installation, you can use the following three command:

```php
use Elsayed85\CopilotChat\CopilotCli;

$cli = new CopilotCli();
$q = "install laravel";
$cli = $cli->init();
$cli->setQuestion($q);
$a = $cli->shell(); // you can use shell() or git() or gitCli()
// call explanation() after shell() or git() or gitCli() to get explanation of the generated cli command
$explanation = $cli->explanation();
dd($a , $explanation);
```

Then We Will Generate Copilot Token and it will be saved locally using cache for (30 min) and 
when it expired another token will be generated automatically.

and Now You can Chat With Github copilot Have Fun :)

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [elsayed kamal](https://github.com/elsayed85)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
