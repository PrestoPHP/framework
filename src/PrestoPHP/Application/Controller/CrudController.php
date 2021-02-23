<?php
namespace PrestoPHP\Framework\Application\Controller;

use PrestoPHP\Framework\Application;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\Request;

class CrudController extends AbstractController
{
    protected $routes = [];
    protected $blacklist = [];
    protected $model;
    protected $modelName;
    protected $modelQuery;
    protected $viewData;
    protected $tableMap;
    protected $tableKeys;
    protected $title;

    public function __construct(Application $app = null)
    {
        parent::__construct($app);
        $class= new \ReflectionClass($this);
        $name = substr($class->getShortName(), 0, strpos($class->getShortName(), "Controller"));
        $this->title = $name;
        $this->modelName = $name;
        $className = "\Model\\$name";
        $classNameQry = "\Model\\{$name}Query";
        $mapName = "\Model\\Map\\{$this->modelName}TableMap";
        $this->tableMap = new $mapName();

        if(class_exists($className)) {
            $this->model = new $className;
            $this->modelQuery = new $classNameQry;
        } else { throw new \Exception("Model for CrudController not Found");
        }

        $this->routes = [
        ["path" => "/{$this->getClassName()}/", "action" => "index", "method" => "GET"],
        ["path" => "/{$this->getClassName()}/add", "action" => "add", "method" => "GET"],
        ["path" => "/{$this->getClassName()}/ajax", "action" => "ajax", "method" => "GET"],
        ["path" => "/{$this->getClassName()}/{id}", "action" => "view", "method" => "GET"],
        ["path" => "/{$this->getClassName()}/edit/{id}", "action" => "edit", "method" => "GET"],
        ["path" => "/{$this->getClassName()}/edit", "action" => "doEdit", "method" => "POST"],
        ["path" => "/{$this->getClassName()}/delete/{id}", "action" => "delete", "method" => "POST"],
        ];
        $this->init();
        $this->getTableKeys($this->blacklist);
    }

    public function getRoutes()
    {
        return $this->routes;
    }

    public function getClassName()
    {
        $classname = (new \ReflectionClass($this))->getShortName();

        return strtolower(str_replace("Controller", "", $classname));
    }

    protected function getData()
    {
        try {
            $this->viewData = $this->modelQuery->find()->toArray();
        } catch (\Exception $e) {
            throw new \Exception("Could not get Model Data");
        }

    }

    protected function blacklist(array $list)
    {
        $this->blacklist = $list;
        for($i = 0; $i < count($this->viewData); $i++) {
            foreach ($this->viewData[$i] as $key => $value) {
                if(in_array($key, $list)) {
                    unset($this->viewData[$i][$key]);
                }
            }
        }
    }

    protected function whitelist(array $list)
    {
        for($i = 0; $i < count($this->viewData); $i++) {
            foreach ($this->viewData[$i] as $key => $value) {
                if(!in_array($key, $list)) {
                    unset($this->viewData[$i][$key]);
                }
            }
        }
    }

    protected function addRelations(array $relations, $with=false)
    {
        foreach ($relations as $relation) {
            ($with === true) ? $funcName = "leftJoinWith$relation" : $funcName = "leftJoin$relation";
            $this->modelQuery->$funcName();
        }
    }

    protected function buildForm($options = null, $blacklist = []) : Form
    {
        $form = $this->application->buildForm(FormType::class, $options);
        try {
            $form->setAction("{$this->application['crudPrefix']}/{$this->getClassName()}/edit");
        } catch (\Exception $e) {
            $form->setAction("/{$this->getClassName()}/edit");
        }
        $form = $this->buildFormFromDatabase($form, $blacklist);
        $form->add('save', SubmitType::class, ['label' => 'Speichern']);
        $form = $form->getForm();

        return $form;
    }

    protected function getTableKeys($blacklist=[])
    {
        unset($this->tableKeys);
        foreach ($this->tableMap->getColumns() as $key) {
            if(!in_array($key->getPhpName(), $blacklist)) {
                $this->tableKeys[] = $key->getPhpName();
            }
        }
    }

    protected function buildFormFromDatabase(FormBuilder $form, $blacklist=[]): FormBuilder
    {
        foreach ($this->tableMap->getColumns() as $key) {
            if(!in_array($key->getPhpName(), $blacklist)) {
                $notNull = $key->isNotNull();
                switch ($key->getType()) {
                case "BIGINT":
                    if (strtolower($key->getPhpName()) == "id") { $form->add($key->getPhpName(), HiddenType::class);
                    } else {
                            $relatedTable = ucwords(str_replace('_', '', $key->getRelatedTableName()));
                        if ($relatedTable != "") {
                            $relatedModelName = "\Model\\{$relatedTable}Query";
                            $query = new $relatedModelName();
                            $relatedData = $query->find();
                            $select = [];
                            foreach ($relatedData as $data) {
                                         $select[$data->getName()] = $data->getId();
                            }

                            $form->add(
                                $key->getPhpName(), ChoiceType::class, [
                                'choices' => $select,
                                ]
                            );
                        } else { $form->add($key->getPhpName(), IntegerType::class, ["required" => $notNull]);
                        }
                    }
                    break;
                case "LONGVARCHAR":
                    $form->add($key->getPhpName(), TextareaType::class, ["required" => $notNull]);
                    break;
                case "TIMESTAMP":
                    $form->add($key->getPhpName(), DateTimeType::class, ["required" => $notNull]);
                    break;
                case "BOOLEAN":
                    $form->add($key->getPhpName(), CheckboxType::class, ["required" => $notNull]);
                    break;
                default:
                    switch (strtolower($key->getPhpName())) {
                    case "email":
                        $form->add($key->getPhpName(), EmailType::class, ["required" => $notNull]);
                        break;
                    case "password":
                    case "passwort":
                        $form->add($key->getPhpName(), PasswordType::class, ["required" => $notNull]);
                        break;
                    default:
                        $form->add($key->getPhpName(), null, ["required" => $notNull]);
                        break;
                    }
                    break;
                }
            }
        }

        return $form;
    }

    protected function sanitizeData($data, $map = null)
    {
        if($map === null) { $map = $this->tableMap;
        }
        foreach ($data as $key=>$value) {
            switch ($map->getColumnByPhpName($key)->getType()) {
            case "TIMESTAMP":
                try {
                        $data[$key] = new \DateTime($value);
                } catch (\Exception $e) {
                      $data[$key] = null;
                }
                break;
            }
        }

        return $data;
    }

    protected function generateFormTemplate(Form $form, $rows = 1)
    {
        $output = "{{ form_start(form) }}";
        $fields = $form->all();
        $output .= "{% if not form.vars.valid %}
		<div class=\"alert alert-danger\" role=\"alert\">
			{{ form_errors(form) }}
		</div>
		{% endif %}
		";
        $rc = 0;
        foreach ($fields as $field) {
            $type = $field->getConfig()->getType()->getInnerType();
            if($type instanceof HiddenType) { continue;
            }
            $rc++;
            if($type instanceof SubmitType) {
                if($rows > 1) { $output .= "</div>";
                }
                $output .= "{{ form_row(form.{$field->getName()}) }}";
            } else {
                if ($rc == 1) { $output .= "<div class=\"row\">";
                }
                $output .= "<div class=\"col-md\">
					{{ form_row(form.{$field->getName()}) }}
				</div>
			";
                if ($rc == $rows) { $output .= "</div>";
                }
            }
            if($rc == $rows) { $rc = 0;
            }
        }
        $output .= "{{ form_end(form) }}";
        file_put_contents(APPLICATION_DIR."/src/views/".$this->modelName."/".strtolower($this->modelName)."/form.twig", $output);
    }

    protected function buildFormTemplate($rows = 1)
    {
        $form = $this->buildForm(null, $this->blacklist);
        $this->generateFormTemplate($form, $rows);
    }


    public function index(Request $request)
    {
        $this->getData();

        return $this->assign(
            [
            "viewData" => $this->viewData,
            "tableKeys" => $this->tableKeys,
            "title" => $this->title
            ]
        );
    }

    public function add(Request $request)
    {
        $form = $this->buildForm(null, $this->blacklist);

        return $this->assign(
            [
            "template" => "edit",
            "form" => $form->createView(),
            "title" => $this->title
            ]
        );
    }

    public function view(Request $request)
    {
        $result = $this->modelQuery->findOneById($request->get("id"));
        if ($result === null) { return $this->application->abort(404);
        }
        $result = $result->toArray();
        unset($result["Password"]);

        return $this->assign(
            [
            "viewData" => $result,
            "title" => $this->title
            ]
        );
    }

    public function edit(Request $request)
    {
        $result = $this->modelQuery->findOneById($request->get("id"));
        if ($result === null) { return $this->application->abort(404);
        }
        $result = $this->sanitizeData($result->toArray());
        $form = $this->buildForm($result, $this->blacklist);

        return $this->assign(
            [
            "form" => $form->createView(),
            "title" => $this->title
            ]
        );
    }

    public function doEdit(Request $request)
    {
        $form = $this->buildForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if($data['Id'] === null) {
                $result = new $this->model();
            } else {
                $result = $this->modelQuery->findOneById($data['Id']);
                if ($result === null) { return $this->application->abort(404);
                }
            }
            $result->fromArray($form->getData());
            $result->save();
        }

        return $this->application->redirect("{$this->application['crudPrefix']}/{$this->getClassName()}");
    }

    public function delete(Request $request)
    {
        $result = $this->modelQuery->findOneById($request->get("id"));
        if ($result === null) { return $this->application->abort(404);
        }
        $result->delete();

        return $this->application->redirect("{$this->application['crudPrefix']}/{$this->getClassName()}");
    }

    public function ajax(Request $request)
    {
        $this->getData();
        $result = [
        "data" => $this->viewData,
        "recordsTotal" => count($this->viewData),
        "recordsFiltered" => count($this->viewData),
        ];

        return $this->application->json($result);
    }

}