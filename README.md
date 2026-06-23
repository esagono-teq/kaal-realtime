# KAAL Realtime 

[![Latest Version on Packagist](https://img.shields.io/packagist/v/kaal/realtime.svg)](https://packagist.org/packages/kaal/realtime) [![License](https://img.shields.io/packagist/l/kaal/realtime.svg)](https://github.com/kaal/realtime/blob/main/LICENSE) 

**Keep Apps Always Live**

KAAL Realtime brings native realtime updates to Laravel Blade without Livewire, Laravel Echo, Pusher, or Reverb.

Wrap any Blade fragment with `@realtime`, and KAAL automatically refreshes that section whenever your application data changes.

- ⚡ Native WebSocket Gateway
- ⚡ One-command installation
- ⚡ Automatic model-driven updates
- ⚡ Signed fragment refreshes
- ⚡ Preserved form and UI state
- ⚡ Production-ready security
- ⚡ Laravel 11+ support

Documentation: https://docs.kaalrealtime.com

---

## 📚 Documentation

Full documentation is available at:

https://docs.kaalrealtime.com

### Getting Started

- Installation
- Gateway Setup
- First Realtime Block
- Configuration

### Core Features

- `@realtime`
- `@preserve`
- `@ignore`
- AJAX Forms (`kaal-submit`)
- Stable Handlers
- Auto Realtime Mode

### Advanced Topics

- Authentication
- Authorization
- Model Scoping
- Multi-Tenant Applications
- Performance Optimization
- Production Deployment

### Security

- Signed URL Architecture
- App Isolation
- Token Authentication
- Refresh Validation

### Reference

- Configuration Options
- Artisan Commands
- Blade Directives
- JavaScript Runtime API

### Troubleshooting

- Gateway Connection Issues
- Refresh Failures
- Authentication Problems
- Common Configuration Mistakes

For complete guides, examples, and API references visit:

https://docs.kaalrealtime.com

---

### Gateway Requirement

KAAL Realtime requires access to a KAAL Gateway instance.
