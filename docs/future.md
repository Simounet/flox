# Future, ideas!?

No order, just thoughts about what needs to be done. You wanna help? [PR welcome!](https://github.com/Simounet/flox/pulls)

## Features

- video games (https://rawg.io/, https://api-docs.igdb.com), books & more?

## Backend

### Fix

- CSRF issue from home to item, then posting a review
- last episode seen on the item cover not updated
- cast not present in some shows like Black Mirror (present in the TMDB API through aggregate_credits instead of credits)

### Improve

- Review with language picker
- PWA
- Remove .gitmodules
- Add search to browser (opensearch)
- Users removal (with ActivityPub activity for reviews and profile deletion)
- Add tests for user creation with admin flag
- Import/export (remove from Client, should only be run from CLI)
- Import/export for admin (add new Models to backup and restore)
- Mail reminders for multiple users (add mail to User model)
- Email + worflow (validation + reset Pass)
- Password strengh
- Otp-> https://github.com/antonioribeiro/google2fa?tab=readme-ov-file#validation-window
- Item creation should use server side fetched content instead of client sent data
- Create a job to clean item's, person, genre (etc.) orphans (without any linked review)
- Rename model Review -> Watchlist
- reorder project paths as a casual Laravel with Vue project
- implement nodeinfo for federation (https://github.com/jhass/nodeinfo)

## Client

- Add note link on UserReview after note added
- Add message as feedback fter note removed
- calendar component translation
- v-hotkey (left/right) used only for the calendar -> dependency removal?
- remove window.config from app/config.js (used for /settings pages)
