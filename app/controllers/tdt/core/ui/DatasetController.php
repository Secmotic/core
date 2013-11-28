<?php

/**
 * The datasetcontroller
 * @copyright (C) 2011,2013 by OKFN Belgium vzw/asbl
 * @license AGPLv3
 * @author Michiel Vancoillie <michiel@okfn.be>
 */
namespace tdt\core\ui;

use tdt\core\auth\Auth;

class DatasetController extends \Controller {

    /**
     * Admin.dataset.view
     */
    public function getIndex(){

        // Set permission
        Auth::requirePermissions('admin.dataset.view');

        // Get all definitions
        $definitions = \Definition::all();

        return \View::make('ui.datasets.list')
                    ->with('title', 'The Datatank')
                    ->with('definitions', $definitions);

        return \Response::make($view);
    }

    /**
     * Admin.dataset.update
     */
    public function getEdit($id){

        // Set permission
        Auth::requirePermissions('admin.dataset.update');

        $definition = \Definition::find($id);
        if($definition){

            // Get source defintion
            $source_definition = $definition->source()->first();

            // Get discovery document
            $browser = new \Buzz\Browser();
            $response = $browser->get(\URL::to('discovery'));

            // Document content
            $discovery = json_decode($response->getContent());

            // Get spec for media type
            // var_dump($source_definition->getType());
            if(empty($discovery->resources->definitions->methods->put->mediaType->{strtolower($source_definition->getType())} )){
                \App::abort('500', 'There is no definition of the media type of this dataset in the discovery document.');
            }
            $mediatype = $discovery->resources->definitions->methods->put->mediaType->{strtolower($source_definition->getType())};

            // Sort parameters
            $parameters_required = array();
            $parameters_optional = array();
            $parameters_dc = array();
            foreach($mediatype->parameters as $parameter => $object){

                // Filter array type parameters

                if(empty($object->parameters)){

                    // Filter Dublin core parameters
                    if(!empty($object->group) && $object->group == 'dc'){
                        $parameters_dc[$parameter] = $object;
                    }else{
                        // Fitler optional vs required
                        if($object->required){
                            $parameters_required[$parameter] = $object;
                        }else{
                            $parameters_optional[$parameter] = $object;
                        }
                    }
                }

            }

            return \View::make('ui.datasets.edit')
                        ->with('title', 'The Datatank')
                        ->with('definition', $definition)
                        ->with('mediatype', $mediatype)
                        ->with('parameters_required', $parameters_required)
                        ->with('parameters_optional', $parameters_optional)
                        ->with('parameters_dc', $parameters_dc)
                        ->with('source_definition', $source_definition);

            return \Response::make($view);

        }else{
            return \Redirect::to('api/admin/datasets');
        }
    }

    /**
     * Admin.dataset.delete
     */
    public function getDelete($id){

        // Set permission
        Auth::requirePermissions('admin.dataset.delete');

        if(is_numeric($id)){
            $definition = \Definition::find($id);
            if($definition){
                // Delete it (with cascade)
                $definition->delete();
            }
        }

        return \Redirect::to('api/admin/datasets');
    }

}