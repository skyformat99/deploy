<?php

namespace App\Model\Finder;

use App\Model\Project;
use Ronanchilvers\Orm\Finder;
use ClanCats\Hydrahon\Query\Expression;

/**
 * Finder for project models
 *
 * @author Ronan Chilvers <ronan@d3r.com>
 */
class ProjectFinder extends Finder
{
    /**
     * Get a list of all projects ordered by project name
     *
     * @return array
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    public function all(array $ids = null)
    {
        $select = $this->select()
            ->orderBy(Project::prefix('last_deployment'), 'desc')
            ->orderBy(Project::prefix('repository'), 'asc')
            ;
        if (is_array($ids)) {
            $select->where(Project::primaryKey(), 'IN', $ids);
        }
        return $select->execute();
    }

    /**
     * Get a project by project key
     *
     * @param string $key
     * @return \App\Model\Project
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    public function forKey($key)
    {
        return $this->select()
            ->where(Project::prefix('key'), $key)
            ->one();
    }

    /**
     * Get a project by project token
     *
     * @param string $token
     * @return \App\Model\Project
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    public function forToken($token)
    {
        return $this->select()
            ->where(Project::prefix('token'), $token)
            ->one();
    }

    /**
     * Check that a given key is unique
     *
     * @param string $key
     * @return boolean
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    public function keyIsUnique($key)
    {
        $existing = $this->select()
            ->where(Project::prefix('key'), $key)
            ->one();

        return !$existing instanceof Project;
    }
}
