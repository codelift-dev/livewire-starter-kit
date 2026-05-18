# Laravel + Livewire Starter Kit

> **CodeLift production-hardened fork.** This branch carries Docker-verified
> production-hardening improvements on top of the official starter kit. A
> follow-up `csp-nonce` branch additionally migrates the CSP to nonce-based
> enforcement. Full verification logs, the rationale for every commit, and the
> before/after diff are documented at:
>
> **https://codelift.lb-product.com/en/articles/laravel-livewire-starter-kit-hardening**
> （日本語: https://codelift.lb-product.com/ja/articles/laravel-livewire-starter-kit-hardening ）
>
> CodeLift verifies official sample code in Docker and publishes the improved
> forks — see [codelift.lb-product.com](https://codelift.lb-product.com).

## Introduction

Our Laravel + [Livewire](https://livewire.laravel.com) starter kit provides a robust, modern starting point for building Laravel applications with a Livewire frontend.

Livewire is a powerful way of building dynamic, reactive, frontend UIs using just PHP. It's a great fit for teams that primarily use Blade templates and are looking for a simpler alternative to JavaScript-driven SPA frameworks like React and Vue.

This Livewire starter kit utilizes Livewire 4, TypeScript, Tailwind, and the [Flux UI](https://fluxui.dev) component library.

If you are looking for the alternate configurations of this starter kit, they can be found in the following branches:

- [workos](https://github.com/laravel/livewire-starter-kit/tree/workos) - if WorkOS is selected for authentication

## Setup

On a fresh clone, the reliable sequence is Composer first, then Node, then migrate + test:

```
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install
npm run build
php artisan test
```

Unlike the React and Vue variants, this Livewire starter does **not** include
`@laravel/vite-plugin-wayfinder`, so `npm run build` does not depend on
`vendor/autoload.php` being present. The order above is still the cleanest
path because migrations and tests assume the `.env`, key, and SQLite database
file are in place.

## Official Documentation

Documentation for all Laravel starter kits can be found on the [Laravel website](https://laravel.com/docs/starter-kits).

## Contributing

Thank you for considering contributing to our starter kit! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

All contributions to the Starter Kits from now on should be made through [Maestro](https://github.com/laravel/maestro).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## License

The Laravel + Livewire starter kit is open-sourced software licensed under the MIT license.
