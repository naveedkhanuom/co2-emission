<?php

namespace App\Services\MRV;

use RuntimeException;

/**
 * The installed template is not the layout the cell map was written for (v8.1).
 *
 * Also an installation problem, but the OPPOSITE one: the file is present and
 * readable, and telling the administrator to install it sends them looking for
 * something that is already there. What has happened is that EAD issued a
 * version whose rows sit elsewhere, so the cell map needs revising before this
 * deployment can export again.
 *
 * Raised by EadWorkbookFiller::assertCellMapFits(), which exists precisely
 * because filling the wrong cells of a regulatory submission fails silently.
 */
class EadTemplateMismatchException extends RuntimeException {}
