<?php

namespace App\Services\MRV;

use RuntimeException;

/**
 * The facility holds more rows than the EAD workbook has room for.
 *
 * Unlike the two template exceptions this is the operator's own data, and it is
 * the operator who resolves it — by splitting the facility, or by consolidating
 * sources — so the message names each table, what it holds and what fits.
 *
 * Refusing rather than truncating is the same judgement assertCellMapFits()
 * makes: a submission that silently omits fifteen emission sources is a
 * misstatement to a regulator, and it looks completely ordinary. One clear
 * error costs less.
 */
class EadCapacityExceededException extends RuntimeException {}
