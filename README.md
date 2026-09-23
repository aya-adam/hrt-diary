# HRT-Diary

A personal platform to track and reflect on hormone replacement therapy (HRT) and transition progress — logging hormone levels, journaling privately or publicly, and keeping on top of medication logistics that are easy to lose track of in daily life.

> **Status:** Work in progress. This is a personal project I use myself and also maintain as a portfolio/demo piece.

## Why this project exists

Tracking HRT well means juggling a few different things at once: how your levels are trending, what you're noticing day to day, and the physical logistics of applying or replacing medication correctly. Most existing tools cover only one of these. HRT-Diary is my attempt to bring them together in one place, built the way I'd actually want to use it.

## Features

- **Journal / Posts** — Write posts about findings, thoughts, and reflections along the way. Each post can be private (just for you) or public (shareable with others).
- **Hormone tracking** — Log hormone values over time and visualize how they trend.
- **Application site rotation** — For gel-based estrogen (or similar topical treatments) that need to be applied to rotating body sites, sometimes multiple times a day, HRT-Diary tells you where to apply next based on your rotation schedule and history.
- **Medication reminders** — Set reminders for recurring medication tasks, such as replacing a gel bottle every *n* days, so nothing gets forgotten.

## Tech stack

**Backend**
- [Symfony](https://symfony.com/)
- [API Platform](https://api-platform.com/) — REST/GraphQL API layer on top of Symfony

**Frontend**
- [React](https://react.dev/)

**Architecture**
- Decoupled SPA frontend consuming a REST API exposed by API Platform
- Deployed on a self-managed webserver

## Roadmap

Rough direction for where this is heading (subject to change, as this is a learning project as much as a tool):

- [x] Backend (Symfony + API Platform)
- [ ] Frontend — starting with a mostly functional build, not yet visually polished
- [ ] Frontend will follow a mobile-first approach

## A note on this repo

This project is built primarily for my own use, so it doesn't currently include a general-purpose setup/installation guide. If you're a recruiter or fellow developer browsing this repo, feel free to look through the code to get a sense of how the backend (Symfony + API Platform) and frontend (React) are structured and how they talk to each other — happy to walk through it in more detail on request.

## License

All rights reserved. This repository is publicly visible for portfolio/demonstration purposes only — see [LICENSE](./LICENSE) for details.
