# Tombstone

A [Flarum](https://flarum.org) extension. When a discussion is permanently deleted, its URL stops being a dead end.

[![Latest Stable Version](https://img.shields.io/packagist/v/andre/tombstone.svg)](https://packagist.org/packages/andre/tombstone)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

## The problem

Flarum has no memory of a discussion once it's permanently deleted. Every link to it &mdash; indexed by Google, shared on social media, bookmarked by a user, posted on another forum &mdash; turns into a bare, unexplained 404. Search engines keep crawling it, lose confidence in the domain, and eventually drop it from the index. Visitors who followed a real link just see a wall.

This has been asked for on the [official Flarum forum](https://discuss.flarum.org/d/19460-friendsofflarum-merge-discussions/60) for years. `FriendsOfFlarum/merge-discussions` solved it for *merged* discussions. Nothing solved it for *deleted* ones &mdash; until now.

## What it does

- **Zero configuration.** The moment this extension is installed, every discussion deletion is automatically recorded: its title, who deleted it, and when. You don't have to do anything.
- **A proper `410 Gone`, not a `404 Not Found`.** These are not the same thing to a search engine. `410` tells Google *"this is gone on purpose, stop indexing it"* &mdash; it clears faster and doesn't hurt your domain's crawl budget the way an unexplained `404` does. Visitors get a clean, readable page instead of a blank error.
- **Optional permanent redirects.** If the content moved somewhere else, point the old URL at the new one with one command. From then on it's a real `301 Moved Permanently`, transparently, for both browsers and search engines.
- **Covers both surfaces.** The forum page (`/d/123-my-topic`) and the API endpoint (`/api/discussions/123`) are both handled, so this works correctly whether a human, a crawler, or another application is the one hitting the old link.
- **No JavaScript, no admin panel, no front-end footprint.** This is a pure backend extension. It costs nothing on pages that work normally: it only does anything at all when Flarum itself would have returned a `404`.

## Installation

```sh
composer require andre/tombstone
php flarum migrate
php flarum cache:clear
```

## Usage

Nothing to configure. Delete a discussion the way you always have; it's tombstoned automatically.

To point an old discussion at a new destination:

```sh
php flarum andre-tombstone:redirect 123 https://example.com/new-location
```

To see everything currently tracked:

```sh
php flarum andre-tombstone:list
```

To stop tracking a discussion and go back to a plain `404` for it:

```sh
php flarum andre-tombstone:forget 123
```

If a discussion was deleted *before* you installed this extension and you want to redirect its old link anyway, add `--force`:

```sh
php flarum andre-tombstone:redirect 456 /d/789-the-new-thread --force
```

## How it works

Flarum's own `Discussion` model dispatches a `Flarum\Discussion\Event\Deleted` event the instant a discussion row is actually deleted (as opposed to merely hidden). This extension listens for that event and writes a tiny, indexed record &mdash; nothing more than the ID, title, timestamp, and whoever did the deleting.

A single PSR-15 middleware is added to both the `forum` and `api` pipelines. It lets every request pass through completely untouched, and only inspects the outgoing response if Flarum itself decided to answer with a `404`. At that point, and only at that point, it checks the discussion ID in the URL against the tombstone table. If there's a match, it swaps in either a `301` to the redirect you configured, or a properly-worded `410 Gone`. Everything else &mdash; every page that isn't a 404 for a deleted discussion &mdash; is unaffected and pays no performance cost whatsoever.

Because IDs are never reused by the database once a discussion is deleted, there is no ambiguity and no risk of a tombstone ever shadowing a real, later discussion.

## Compatibility

Requires `flarum/core: ^2.0.0` and PHP `^8.2`. No other extension is required.

## License

MIT. See [LICENSE](LICENSE).

---

Developed by **andre**.
