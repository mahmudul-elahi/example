# Project Features Roadmap & Specifications

This document serves as the master feature catalogue for the platform, containing the **100 Core Project Features** alongside **Advanced & Domain-Specific Feature Extensions** (Pet Memorials, Grief Support, AI Tools, Merchandising, and Community).

---

## Part 1: Core 100 Planned Features

### User Profile & Account Management
1. **User Avatar Upload Endpoint**: Dedicated upload and thumbnail processing for user avatars.
2. **User Profile Update**: Bio, phone number, social links, date of birth, and location.
3. **Public User Profile API**: Public view showing public memorials, published posts, and stats.
4. **Follow / Unfollow Users**: Social graph for following creators and friends.
5. **Followers & Following Lists**: Paginated list of followers and following accounts.
6. **Block Users**: Prevent unwanted interactions, messages, and content visibility.
7. **Mute Users**: Hide activities and posts from users without blocking or notifying them.
8. **User Activity Feed**: Chronological stream of activities from followed users.
9. **Online Status / Last Seen**: Real-time or recent presence indicator.
10. **Account Deactivation / Deletion**: Self-service deactivation and GDPR-compliant erasure.
11. **Two-Factor Authentication (2FA)**: TOTP / Authenticator app support.
12. **Social Login**: OAuth2 login via Google, Facebook, and Apple.
13. **Change Email**: Verification workflow with confirmation sent to both old and new addresses.
14. **Change Password**: Secure credential update with current password verification.
15. **Session Management**: List active tokens/sessions and revoke individually or all other devices.

### Memorials & Tributes
16. **Image Upload for Memorials**: Cover and profile picture uploads with optimization.
17. **Multiple Image Gallery per Memorial**: Multi-image uploads with captions and sorting.
18. **Memorial Tributes**: Virtual candle lighting and digital flower sending with tribute messages.
19. **Memorial Timeline (Life Events)**: Milestone timeline (adoption date, birthdays, favorite moments, passing date).
20. **Memorial Anniversary Reminders**: Automatic notifications for passing dates and birthdays.
21. **Memorial Sharing & Collaborators**: Invite family/friends to collaborate on memorial content.
22. **Memorial Privacy Controls**: Public, friends-only, or private link-only access levels.

### Blog & Publishing
23. **Image Upload for Blog Posts**: Featured image and in-body media uploads.
24. **Blog Post Drafts & Auto-Save**: Periodic auto-saving of draft content.
25. **Blog Post Scheduled Publishing**: Queue-driven scheduled publication at a future timestamp.
26. **Blog Post Series / Multi-Part Posts**: Grouping related articles into a sequential series.
27. **Blog Post Reading Time Estimate**: Calculated dynamically based on word count.
28. **Blog Post Views Tracking**: Unique and total view counting with debounce protection.
29. **Blog Post Full-Text Search**: Keyword matching across title, excerpt, and body content.
30. **Blog Post Filtering by Category & Tags**: Query filters by taxonomy.
31. **Blog Post Sorting**: Sort by popular (views/likes), recent, or trending.

### E-Commerce & Products
32. **Image Upload for Products**: Product thumbnails and showcase images.
33. **Multiple Image Gallery per Product**: Multi-angle product photo carousel.
34. **Product Categories / Collections**: Hierarchical product taxonomy and seasonal collections.
35. **Product Variants**: Attributes such as size, color, material, and engraving options.
36. **Product Reviews & Star Ratings**: Verified buyer reviews, comments, and average ratings.
37. **Product Search**: Search by title, SKU, tags, and description.
38. **Product Sorting & Filtering**: Filter by price range, availability, rating, and sort order.
39. **Product Wishlist**: Save favorite items for future purchase.
40. **Shopping Cart**: Guest and authenticated persistent cart management.
41. **Checkout / Order Placement**: Multi-step checkout with address and payment capture.
42. **Order History**: User dashboard listing past purchases and order details.
43. **Order Status Tracking**: Lifecycle tracking (Processing, Shipped, Delivered, Cancelled).
44. **Payment Integration (Stripe)**: Credit card and digital payment processing.
45. **Payment Integration (PayPal)**: PayPal Express checkout.
46. **Discount Codes / Coupons**: Percentage and fixed-amount coupon validation and limits.
47. **Invoice Generation (PDF)**: Automated downloadable receipt/tax invoice PDF upon order completion.
48. **Shipping Address Management**: Saved addresses with default billing and shipping flags.

### Social Interactions, Comments & Bookmarks
49. **Nested Comment Threads**: Multi-level hierarchical replies.
50. **Comment Reactions**: Emoji reactions (like, heart, care, sympathy) on comments.
51. **Comment Editing History**: View edit timestamps and previous versions.
52. **Comment Pinning**: Pin top or meaningful comments by content authors.
53. **Mention Users in Comments (@username)**: Auto-complete mentions with push/in-app notifications.
54. **Rich Text / Markdown in Comments**: Formatting support with sanitization.
55. **Bookmark / Save Posts**: Quick saving of blog articles.
56. **Bookmark / Save Memorials**: Save memorials to pay respects regularly.
57. **Bookmark / Save Products**: Save items to personal lists.
58. **Bookmark Collections / Folders**: Organize bookmarks into custom named folders.
59. **Share Content Externally**: Share link generation with OpenGraph / Twitter Card preview meta tags.

### Moderation, Chat & Notifications
60. **Content Reporting / Flagging**: User reports for spam, harassment, or inappropriate content.
61. **Content Moderation Queue (Admin)**: Review, approve, hide, or dismiss flagged items.
62. **Direct Messaging / Chat**: 1-on-1 private messaging between connected users.
63. **Group Chat**: Multi-user chat rooms for support circles or groups.
64. **Real-Time Messaging (WebSocket)**: Instant delivery using Laravel Reverb / WebSockets.
65. **Message Read Receipts**: Delivered and seen indicators.
66. **Email Notifications for Comments**: Notify author when someone comments on their content.
67. **Email Notifications for Likes**: Aggregate like notifications.
68. **Email Notifications for Friend Requests**: Alerts on incoming connection requests.
69. **Email Notification Preferences**: Granular user toggles for each notification type.
70. **Push Notification Support (Firebase)**: Native and Web push messaging.
71. **Notification Grouping / Batching**: Bundling notifications (e.g., *"5 people lit a candle today"*).

### Administration & Operations
72. **Admin User Ban / Suspend**: Temporary or permanent account suspensions.
73. **Admin User Roles Management UI**: Role and permission assignments via UI.
74. **Admin Analytics Dashboard**: Growth metrics, registration counts, content velocity, and revenue.
75. **Admin Bulk Actions**: Multi-select approve, reject, or delete items.
76. **Admin Audit Log**: Detailed history of administrative actions and changes.
77. **Admin Content Approval Workflow**: Optional publication gate for pending content.
78. **Admin Site Settings**: Dynamic configuration for site name, logo, contact, and maintenance mode.

### Platform Architecture & Discovery
79. **Global Search API**: Unified search across Memorials, Blog Posts, Products, and Users.
80. **Tag-Based Browsing / Tag Cloud**: Browse content grouped by tags.
81. **Trending Tags**: Algorithmically surfaced popular topics.
82. **Related Content Suggestions**: Algorithmic recommendations for related posts and memorials.
83. **Personalized Content Recommendations**: Feed tailored to user browsing history.
84. **Cursor-Based Pagination**: Optimized infinite scroll performance.
85. **Content Export**: Export individual memorials or blog posts to PDF.
86. **Data Export (GDPR)**: One-click "Download My Data" archive (JSON + media).
87. **API Rate Limiting per User Tier**: Throttling policies for guests, users, and API clients.
88. **Webhook Support**: Outgoing webhooks for order and event integrations.
89. **RSS Feed for Blog**: Standard RSS/Atom feed for syndicated readers.
90. **Sitemap Generation**: Dynamic XML sitemap for SEO discovery.
91. **Multi-Language / Localization**: Localization strings and language switching.
92. **Dark Mode Preference**: Saved user theme preference.
93. **Contact Us / Support Tickets**: In-app support request submission and management.
94. **FAQ / Help Center**: Categorized self-service knowledge base.
95. **Terms of Service & Privacy Policy Acceptance**: Version-tracked legal consent tracking.
96. **Referral System**: Invite-a-friend links with tracking.
97. **Achievement Badges / Gamification**: Earnable milestones for community engagement.
98. **User Reputation / Karma System**: Community trust score.
99. **Content Versioning / Edit History**: Audit trail of revisions for posts and pages.
100. **Soft-Delete Recovery**: Admin and user trash can with restore capabilities.

---

## Part 2: Extended & High-Impact Feature Ideas

These innovative feature extensions specifically leverage the platform's niche in **Pet Memorials, Grief Support, and Custom Commerce**:

### 1. Physical & Tangible Memorials
* **Physical Gravestone / Urn QR Code Generator**: Generates vector/printable QR codes linking directly to a pet's memorial, suitable for laser engraving on headstones, urns, and collars.
* **One-Click Printable Keepsake Book (PDF / Hardcover Photobook)**: Compiles the memorial timeline, eulogy, tributes, and photos into a formatted printable album.
* **Apple & Google Wallet Memorial Keepsake Pass**: Digital remembrance card stored in mobile wallets with pet photo and quick memorial link.

### 2. Emotional & Grief Support
* **Voice & Audio Memory Tributes**: Record or upload soundbites (a cat's purr, a dog's bark, or spoken eulogies).
* **Interactive "Rainbow Bridge" Constellation Map**: Interactive canvas/3D starfield where each star represents a departed pet and lets visitors light virtual candles.
* **"On This Day" Throwback Reminders**: Annual notifications reminding owners of memories uploaded in previous years.
* **Digital Paw-Print & Hand-Drawn Signature Canvas**: Guestbook canvas for visitors to draw a paw print or hand-sign condolences.
* **"In Memory Of" Shelter Donation Drive**: Link memorials to verified animal shelters so visitors can donate in the pet's honor.
* **Pet Loss Grief Support Directory**: Curated directory of certified pet loss grief counselors, support circles, and 24/7 hotlines.
* **Memorial Pre-Approval Queue**: Allows grieving owners to approve tributes, candles, and notes before they become visible.

### 3. AI-Assisted Creative Tools
* **AI Eulogy / Obituary Assistant**: Guided questionnaire that drafts a heartfelt memorial story.
* **AI Pet Memorial Artwork Generator**: Converts photos into watercolor, charcoal sketch, or oil canvas portraits.
* **Automated Tribute Slideshow Maker**: Assembles photos, dates, and soft background music into a 60-second video tribute.
* **Photo Restoration & Background Cleanup**: Restores and sharpens old, blurry pet photos and removes background clutter.
* **Empathetic Content Sensitivity Filter**: Automatic sentiment filter to keep memorial spaces respectful and free from trolling.

### 4. Specialized Commerce & Merchandising
* **Live Personalized Product Configurator**: Real-time 2D/3D preview of custom-engraved urns, jewelry, and stones showing pet name, dates, and photo before checkout.
* **"Send Sympathy Gift" to Creator**: Allows friends to send physical sympathy gift boxes or flowers to the memorial creator without disclosing the creator's private shipping address.
* **Buy Now, Pay Later (BNPL)**: Klarna / Afterpay integration for larger memorial monuments or fine keepsake jewelry.
* **Memorial Anniversary Subscription Kits**: Annual remembrance package (replacement candle, memorial wildflower seeds).

### 5. Community & Pet Safety
* **Lost & Found Emergency Pet Alert Network**: Geofenced missing pet alert sending instant push notifications to nearby users and generating printable flyers with QR codes.
* **Virtual Memorial Ceremonies (Live Gathering)**: Scheduled online memorial gathering with synchronized virtual candlelight vigils.
* **Breed & Species Circles**: Dedicated communities for breed-specific discussions and senior pet care.

### 6. Modern Security & Access
* **Memorial Legacy Contact / Digital Will**: Nominate a secondary trusted user to manage memorials if the primary account becomes inactive.
* **Passkeys (WebAuthn / Biometrics)**: Passwordless biometric login with Touch ID / Face ID.
* **Magic Link Login**: Fast, passwordless one-time email login links.
* **Suspicious Login & Device Anomaly Detection**: Alert users on unrecognized device or IP logins with one-click token revocation.
* **PWA Offline Viewing**: Progressive Web App caching so users can view their pet's memorial even without an internet connection.
* **Live Presence Indicator**: Subtle live count on memorial pages (*"3 people are currently visiting Max's memorial"*).
