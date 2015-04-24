<?php

namespace Thru\ActiveRecordMigrations;

use Commando\Command;
use Symfony\Component\Yaml\Yaml;
use Thru\ActiveRecord\ActiveRecord;

class Migrator
{
	static public function Main(){
		$start = microtime(true);

		$migrator_command = new Command();
		$migrator_command->option('i')
			->aka('import')
			->describedAs("Import fixtures from .")
			->boolean();
		$migrator_command->option('e')
			->aka('export')
			->describedAs("Export fixtures from .")
			->boolean();
		$migrator_command->option('class')
			->describedAs("fill this in");

		$migrator_command->option('p')->aka('purge')
			->boolean()
			->describedAs("Purge tables before import & rebuild");

		$migrator_command->option('migration_path')->aka('output')
			->require()
			->default("fixtures");

		$classes_to_compute = [];

		if($migrator_command['class']){
			$classes_to_compute[] = $migrator_command['class'];
		}else{
			$array = Yaml::parse(file_get_contents(ACTIVERECORDMIGRATIONS_CWD . "/fixtures.yaml"));
			foreach($array['models'] as $model){
				$classes_to_compute[] = $model;
			}
		}

		if($migrator_command['purge']){
			foreach ($classes_to_compute as $class){
				Migrator::Purge($class);
			}
		}

		if($migrator_command['export']) {
			foreach ($classes_to_compute as $class) {
				Exporter::Run($class, $migrator_command['migration_path']);
			}
			$end = microtime(true);
			$time_to_execute = number_format($end - $start, 2);
			echo "Export finished in {$time_to_execute} seconds\n\n";
		}elseif($migrator_command['import']) {
			foreach ($classes_to_compute as $class) {
				Importer::Run($class, $migrator_command['migration_path']);
			}
			$end = microtime(true);
			$time_to_execute = number_format($end - $start, 2);
			echo "Import finished in {$time_to_execute} seconds\n\n";
		}else{
			$migrator_command->printHelp();
		}

	}

	static public function Purge($class){
		/* @var $o ActiveRecord */
		$o = new $class();
		echo "Purging {$class} table {$o->get_table_name()}";
		$o->delete_table();
		echo " [Done]\n";
	}
}