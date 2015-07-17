#!/bin/bash

# Distilled from https://peteraba.com/blog/phpmetrics-of-popular-projects/
REPOS='
  https://github.com/agavi/agavi
  https://github.com/auraphp/Aura.Framework_Project
  https://github.com/cakephp/cakephp
  https://github.com/EllisLab/CodeIgniter
  https://github.com/colindean/deano
  https://github.com/bcosca/fatfree
  https://github.com/mikecao/flight
  https://github.com/fuel/fuel
  https://github.com/laravel/laravel
  https://github.com/sofadesign/limonade
  https://github.com/nette/nette
  https://github.com/phavour/phavour
  https://github.com/panique/php-mvc-advanced
  https://github.com/dracony/PHPixie
  https://github.com/popphp/popphp2
  https://github.com/silexphp/Silex
  https://github.com/codeguy/Slim
  https://github.com/symfony/symfony1
  https://github.com/symfony/symfony
  https://git.typo3.org/Packages/TYPO3.CMS.git
  https://github.com/yiisoft/yii
  https://github.com/yiisoft/yii2
  https://github.com/zendframework/zf1
  https://github.com/zendframework/zf2

  https://github.com/drupal/drupal
  https://github.com/joomla/joomla-framework
  https://github.com/getgrav/grav
  https://github.com/pagekit/pagekit
  https://github.com/WordPress/WordPress
';

TESTDIR='test-repos'

mkdir -p $TESTDIR
cd $TESTDIR
for i in $REPOS; do
  git clone $i
done
