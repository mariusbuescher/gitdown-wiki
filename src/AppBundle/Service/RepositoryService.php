<?php

namespace AppBundle\Service;

use \Gitonomy\Git\Repository;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class RepositoryService
{
    protected $rootPath;
    
    protected $adminRepository;
    
    function __construct($rootPath, $adminRepository = '')
    {
        $this->rootPath = $rootPath;
        $this->adminRepository = $adminRepository;
    }
    
    public function getRepository($repositoryPath)
    {
        $path = realpath($this->rootPath . '/' . $repositoryPath);
        
        if ($path === false) {
            throw new \InvalidArgumentException(sprintf('Repository "%s" does not exist.', $repositoryPath));
        } else if (\Gitonomy\Git\Admin::isValidRepository($path) === false) {
            throw new \InvalidArgumentException(sprintf('Repository "%s" is not a valid git repository.', $repositoryPath));
        }
        
        $repository = new Repository($path, array(
            'debug' => false
        ));
        
        return $repository;
    }
    
    public function getAllRepositories()
    {
        $finder = new Finder();
        $finder->directories()
            ->in($this->rootPath)
            ->depth('== 0')
            ->notName($this->adminRepository);
        
        $repositories = array();
        
        foreach ($finder as $directory) {
            $repository = $this->getRepository($directory->getFileName());
            array_push($repositories, array(
                'slug' => $directory->getFileName(),
                'name' => $repository->getDescription()
            ));
        }
        
        return $repositories;
    }
    
    public function createRepository($repositoryPath)
    {
        $path = $this->rootPath . '/' . $repositoryPath;
        
        if (realpath($path) === false) {
            $fs = new Filesystem();
            $fs->mkdir($path);
        } else if (\Gitonomy\Git\Admin::isValidRepository($path)) {
            throw new \InvalidArgumentException(sprintf('Repository "%s" already exists', $repositoryPath));
        }
        
        $repository = \Gitonomy\Git\Admin::init($path, false);
        
        return $repository;
    }
}
