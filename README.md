# Aleph∞One

**A One Health-driven relational web application for integrated epidemiological data management.**

[![License: AGPL-3.0](https://img.shields.io/badge/license-AGPL--3.0-blue.svg)](LICENSE)
[![DOI](https://zenodo.org/badge/DOI/10.5281/zenodo.21227804.svg)](https://doi.org/10.5281/zenodo.21227804)

Aleph∞One unifies the essential components of infectious-disease research into a single
relational platform, preserving contextual relationships and end-to-end traceability from
field collection through laboratory analysis to downstream outputs across the human, animal
and environmental domains.

## Features

- Sample registration and processing (human, animal, environmental, parasite)
- Experiment and laboratory workflow tracking
- Biobank / storage (tube) management
- Genomic sequence and nucleic-acid records
- Literature and project coordination, with role-based access control
- Interactive dashboards (Chart.js) and geographic maps (Leaflet)
- CSV and Excel (.xlsx) import/export, plus a versioned REST API for external analytical workflows

## Tech stack

PHP 8.4 · Laravel 12 · Livewire 3 · Tailwind CSS · MySQL/SQLite · Laravel Sanctum (API).

## REST API

A token-authenticated, versioned API is available under `/api/v1`. Obtain a token with
`POST /api/v1/auth/token` and read project-scoped resources (`projects`, `animal-samples`,
`human-samples`, `experiments`, `sequences`). Patient identifiers are never exposed.

## Security

Multi-factor authentication (passkeys/WebAuthn and TOTP), role-based access control,
application-layer encryption of direct patient identifiers, a full audit trail, files served
only behind authentication, and a dependency vulnerability audit.

## License

Aleph∞One is released under the **GNU Affero General Public License v3.0** (see [LICENSE](LICENSE)).
A commercial license is available on request.

## Citation

If you use Aleph∞One in your research, please cite it via [`CITATION.cff`](CITATION.cff) and
the accompanying article (DOI to be added on publication).
