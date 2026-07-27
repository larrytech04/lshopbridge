<?php

namespace App\Services\Import\Connectors;

use App\Models\ImportSource;

/**
 * Represents manually-created and duplicated products — there is no external
 * system to connect to, so this connector is always "connected" and only
 * implements testConnection() as a trivial always-ok check.
 */
class NativeConnector extends AbstractConnector
{
    public function capabilities(): array
    {
        return ['test_connection'];
    }

    public function testConnection(ImportSource $source): array
    {
        return ['ok' => true, 'message' => 'Native products require no external connection.'];
    }
}
