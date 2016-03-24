<?hh
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the same directory.
 */
namespace Facebook\ShipIt;

class ShipItRepoException extends \Exception {
  public function __construct(?ShipItRepo $repo, string $message) {
    if ($repo !== null) {
      $message = get_class($repo) . ": $message";
    }
    parent::__construct($message);
  }
}

/**
 * Repo handler interface
 * For agnostic communication with git, hg, etc...
 */
abstract class ShipItRepo {
  /**
   * @param $path the path to the repository
   */
  public function __construct(
    protected string $path,
    string $branch,
  ) {
    $this->setBranch($branch);
  }

  const VERBOSE_FETCH = 1;
  const VERBOSE_SHELL = 2;
  const VERBOSE_SHELL_OUTPUT = 4;
  const VERBOSE_SHELL_INPUT = 8;

  // Level of verbosity for -v option
  const VERBOSE_STANDARD = 3;

  static public $VERBOSE = 0;

  const TYPE_GIT = 'git';
  const TYPE_HG  = 'hg';

  public function getPath(): string {
    return $this->path;
  }


  /**
   * Implement to allow changing branches
   */
  protected abstract function setBranch(string $branch): bool;

  /**
   * Updates our checkout
   */
  public abstract function pull(): void;

  public static function typedOpen<Trepo as ShipItRepo>(
    classname<Trepo> $interface,
    string $path,
    string $branch,
  ): Trepo {
    $repo = ShipItRepo::open($path, $branch);
    invariant(
      $repo instanceof $interface,
      '%s is a %s, needed a %s',
      $path,
      get_class($repo),
      $interface,
    );
    return $repo;
  }

  /**
   * Factory
   */
  public static function open(
    string $path,
    string $branch,
  ): ShipItRepo {
    if (file_exists($path.'/.git')) {
      return new ShipItRepoGIT($path, $branch);
    }
    if (file_exists($path.'/.hg')) {
      return new ShipItRepoHG($path, $branch);
    }
    throw new ShipItRepoException(
      null,
      "Can't determine type of repo at ".$path,
    );
  }

  protected static function shellExec(
    string $path,
    ?string $stdin,
    ...$args
  ): string {
    return ShipItUtil::shellExec($path, $stdin, self::$VERBOSE, ...$args);
  }
}