<?php
return [
    // Existing installations retain the legacy default. Fresh installations
    // explicitly set an empty value so privileges never follow a recycled ID.
    'restricted_full_user_ids' => array_values(array_filter(array_map('intval',
        explode(',', (string) env('BGS_RESTRICTED_FULL_USER_IDS', '4'))
    ), fn ($id) => $id > 0)),
];
