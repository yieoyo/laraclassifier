# Introduction

LaraClassifier API specification and documentation.

<aside>
    <strong>Base URL</strong>: <code>https://demo.laraclassifier.local</code>
</aside>

This documentation aims to provide all the information you need to work with our API.

<aside>As you scroll, you'll see code examples for working with the API in different programming languages in the dark area to the right (or as part of the content on mobile).
You can switch the language used with the tabs at the top right (or from the nav menu at the top left on mobile).</aside>
<p><strong>Important:</strong> By default the API uses an access token set in the <strong><code>/.env</code></strong> file with the variable <code>APP_API_TOKEN</code>, whose its value
need to be added in the header of all the API requests with <code>X-AppApiToken</code> as key. On the other hand, the key <code>X-AppType</code> must not be added to the header... This key is only useful for the included web client and for API documentation.</p>
<p>Also, by default the default app's country will be selected if the <strong><code>countryCode</code></strong> query parameter is not filled during API calls. If a default country is not set for the app, the most populated country will be selected. Same for the language, which the default app language will be selected if the <strong><code>languageCode</code></strong> query parameter is not filled.</p>

