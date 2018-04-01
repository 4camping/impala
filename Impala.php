<?php

namespace Impala;

use Nette\Application\Responses\JsonResponse,
    Nette\Application\UI\Control,
    Nette\Application\IPresenter,
    Nette\ComponentModel\IComponent,
    Nette\Http\IRequest,
    Nette\Utils\DateTime,
    Nette\Localization\ITranslator,
    PHPExcel,
    PHPExcel_IOFactory,
    PHPExcel_Style_Alignment,
    PHPExcel_Writer_Excel2007;

/** @author Lubomir Andrisek */
final class Impala extends Control implements IImpalaFactory {

    /** @var array */
    private $config;

    /** @var IBuilder */
    private $grid;

    /** @var IGridFactory */
    protected $gridFactory;

    /** @var array */
    private $header;

    /** @var IHelp */
    private $helpRepository;

    /** @var IImportFormFactory */
    private $importFormFactory;

    /** @var IRequest */
    private $request;

    /** @var IBuilder */
    private $row;

    /** @var ITranslator */
    private $translatorRepository;

    public function __construct(array $config, IGridFactory $gridFactory, IHelp $helpRepository, IImportFormFactory $importFormFactory, IRequest $request, ITranslator $translatorRepository) {
        parent::__construct(null, null);
        $this->config = $config;
        $this->gridFactory = $gridFactory;
        $this->helpRepository = $helpRepository;
        $this->importFormFactory = $importFormFactory;
        $this->request = $request;
        $this->translatorRepository = $translatorRepository;
    }

    public function attached(IComponent $presenter): void {
        parent::attached($presenter);
        if ($presenter instanceof IPresenter) {
            if(!empty($this->grid->getCollection())) {
                $this->grid->attached($this);
            }
            if(!empty($this->row->getCollection())) {
                $this->row->attached($this);
            }
        }
    }

    public function create(): Impala {
        return $this;
    }

    protected function createComponentGrid(): IGridFactory {
        return $this->gridFactory->create()
            ->setGrid($this->grid);
    }

    protected function createComponentRowForm(): IRowFormFactory {
        $offsets = $this->row->limit(1)->prepare()->getOffsets();
        $form = $this->row->row(0, reset($offsets));
        if($this->row->isEdit()) {
            $form = $this->row->getEdit()->after($form);
        }
        return $form;
    }

    protected function createComponentImportForm(): IImportFormFactory {
        return $this->importFormFactory->create()
                    ->setService($this->grid->isImport() ? $this->grid->getImport() : $this->row->getImport());
    }

    public function getGrid(): IBuilder {
        return $this->grid;
    }

    private function getHeader(array $row, array $header): void {
        foreach ($row as $key => $column) {
            mb_detect_encoding($column, 'UTF-8', true) == false ? $column = trim(iconv('windows-1250', 'utf-8', $column)) : $column = trim($column);
            if (isset($header->$column)) {
                foreach ($header->$column as $feed => $value) {
                    if (!isset($this->header[$feed]) and is_numeric($value)) {
                        $this->header[$feed] = [$value => $key];
                    } elseif (!isset($this->header[$feed]) and is_bool($feed)) {
                        $this->header[$feed] = $key;
                    } elseif ('break' == $value and ! isset($this->header[$feed])) {
                        $this->header[$feed] = $key;
                    } elseif ('break' == $value and isset($this->header[$feed])) {

                    } elseif (is_array($header->$feed)) {
                        is_numeric($value) ? $this->header[$feed][$value] = $key : $this->header[$feedColumn][] = $key;
                    } elseif (is_numeric($value)) {
                        $this->header[$feed] = [0 => $key, $value => $header->$feed];
                    }
                }
            }
        }
        if (!empty($this->header)) {
            foreach (json_decode($this->grid->getImport()->getSetting()->validator) as $validator => $value) {
                if (!isset($this->header[$validator])) {
                    $this->header = $this->translatorRepository->translate('Header does not contains validator') . ' ' . $this->translatorRepository->translate($validator) . '.';
                }
            }
        }
    }

    private function getDivider(string $file): string {
        $dividers = [];
        foreach ([',', ';', '"'] as $divider) {
            $handle = fopen($file, 'r');
            $line = fgetcsv($handle, 10000, $divider);
            fclose($handle);
            $dividers[count($line)] = $divider;
        }
        ksort($dividers);
        $divider = array_reverse($dividers);
        return array_shift($divider);
    }

    private function getResponse(): array {
        $response = ['_file' => $this->grid->getPost('_file'),
            'data' => $this->grid->getPost('data'),
            'divider' => $this->grid->getPost('divider'),
            'header' => $this->grid->getPost('header'),
            'filters' => $this->grid->getPost('filters'),
            'offset' => $this->grid->getPost('offset'),
            'status' => $this->grid->getPost('status'),
            'stop'=>$this->grid->getPost('stop'),
        ];
        if(null == $response['data'] = $this->grid->getPost('data')) {
            $response['data'] = [];
        }
        return $response;
    }
    
    public function handleCrop(): void {
        $response = [];
        if($this->row->isEdit()) {
            $response = $this->row->getEdit()->crop($this->row->getPost('image'), $this->row->getPost('row'));
        }
        $this->presenter->sendResponse(new JsonResponse($response));
    }

    public function handleDelete(): void {
        $response = [];
        if($this->row->isEdit()) {
            $response = $this->row->getEdit()->delete($this->row->getPost('image'), $this->row->getPost('row'));
        }
        $this->presenter->sendResponse(new JsonResponse($response));
    }

    public function handleDone(): void {
        $this->grid->log('done');
        $service = 'get' . ucfirst($this->grid->getPost('status'));
        $this->presenter->sendResponse(new JsonResponse($this->grid->$service()->done($this->getResponse(), $this)));
    }
    
    public function handleExcel(): void {
        $excel = new PHPExcel();
        $folder = $this->grid->getExport()->getFolder();
        !file_exists($folder) ? mkdir($folder, 0755, true) : null;
        $title = 'export';
        $properties = $excel->getProperties();
        $properties->setTitle($title);
        $properties->setSubject($title);
        $properties->setDescription($title);
        $excel->setActiveSheetIndex(0);
        $sheet = $excel->getActiveSheet();
        $sheet->setTitle(substr($title, 0, 31));
        $letter = 'a';
        foreach($this->grid->prepare()->getOffset(0) as $column => $value) {
            if($value instanceof DateTime || false == $this->grid->getAnnotation($column, ['unrender', 'hidden', 'unexport'])) {
                $sheet->setCellValue($letter . '1', ucfirst($this->translatorRepository->translate($column)));
                $sheet->getColumnDimension($letter)->setAutoSize(true);
                $sheet->getStyle($letter . '1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $letter++;
            }
        }
        $file = $this->grid->getId('excel') . '.xls';
        $writer = new PHPExcel_Writer_Excel2007($excel);
        $writer->save($folder . '/' .$file);
        $response = new JsonResponse($this->grid->getExport()->prepare([
            '_file' => $file,
            'filters' => $this->grid->getPost('filters'),
            'offset' => 0,
            'sort' => $this->grid->getPost('sort'),
            'status' => 'excel',
            'stop' => $this->grid->getSum()], $this));
        $this->presenter->sendResponse($response);
    }

    public function handleExport(): void {
        $folder = $this->grid->getExport()->getFolder();
        !file_exists($folder) ? mkdir($folder, 0755, true) : null;
        $header = '';
        foreach($this->grid->prepare()->getOffset(0) as $column => $value) {
            if($value instanceof DateTime || false == $this->grid->getAnnotation($column, ['unrender', 'hidden'])) {
                $header .= $this->grid->translate($column) . ';';
            }
        }
        $file = $this->grid->getId('export') . '.csv';
        file_put_contents($folder . '/' . $file, $header);
        $response = new JsonResponse($this->grid->getExport()->prepare([
                '_file' => $file,
                'filters' => $this->grid->getPost('filters'),
                'offset' => 0,
                'sort' => $this->grid->getPost('sort'),
                'status' => 'export',
                'stop' => $this->grid->getSum()], $this));
        $this->presenter->sendResponse($response);
    }

    public function handleImport(): void {
        $import = $this->grid->isImport() ? $this->grid->getImport() : $this->row->getImport();
        $path = $import->getFolder();
        $setting = $import->getSetting();
        $header = json_decode($setting['mapper']);
        $divider = $this->getDivider($path);
        $handle = fopen($path, 'r');
        while (false !== ($row = fgets($handle, 10000))) {
            $before = $row;
            $row = $this->sanitize($row, $divider);
            if (empty($this->header)) {
                $offset = strlen($before);
                $this->getHeader($row, $header);
            } elseif (!empty($this->header)) {
                break;
            }
        }
        $response = new JsonResponse($import->prepare(['divider'=>$divider,
                                    'header'=>$this->header,
                                    '_file'=> $builder->getPost('_file'),
                                    'link'=> $this->link('run'),
                                    'offset'=> $offset,
                                    'status'=>'import',
                                    'stop' => filesize($path)], $this));
        $this->presenter->sendResponse($response);
    }

    public function handleMove(): void {
        $response = [];
        if($this->row->isEdit()) {
            $this->row->getEdit()->move($this->row->getPost('image'), $this->row->getPost('row'));
        }
        $this->presenter->sendResponse(new JsonResponse($response));
    }

    public function handlePrepare(): void {
        $this->grid->log('prepare');
        $data = ['filters' => $this->grid->getPost('filters'),
            'offset' => 0,
            'sort' => $this->grid->getPost('sort'),
            'status' => 'service',
            'stop' => $this->grid->prepare()->getSum()];
        $response = new JsonResponse($this->grid->getService()->prepare($data, $this));
        $this->presenter->sendResponse($response);
    }

    public function handlePut(): void {
        $response = ['name' => $this->row->getPost('name')];
        if($this->row->getPost('image')) {
            file_put_contents($image = $this->row->getEdit()->getFolder() . $response['name'], base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $this->row->getPost('file'))));
        } else {
            file_put_contents($this->row->getEdit()->getFolder() . $response['name'], $this->row->getPost('file'));
        }
        $this->presenter->sendResponse(new JsonResponse($response));
    }

    public function handleResize(): void {
        if($this->grid->isImport()) {
            $response = $this->grid->getImport()->resize($this->grid->getPost(''));           
        } else if($this->row->isImport()) {
            $response = $this->row->getImport()->resize($this->row->getPost(''));                       
        }
        $this->presenter->sendResponse(new JsonResponse($response));
    }
    
    public function handleRun(): void {
        $response = $this->getResponse();
        if ('import' == $response['status']) {
            $service = $this->grid->getImport();
            $path = $this->grid->getImport()->getFolder();
            $handle = fopen($path, 'r');
            for($i = 0; $i < $speed = $service->speed($this->config['speed']); $i++) {
                fseek($handle, $response['offset']);
                $offset = fgets($handle);
                if($response['stop'] == $response['offset'] = ftell($handle)) {
                    $i = $speed;
                }
                $response['row'] = [];
                $offset = $this->sanitize($offset, $response['divider']);
                foreach ($response['header'] as $headerId => $header) {
                    if (is_array($header)) {
                        foreach ($header as $valueId => $value) {
                            $response['row'][$headerId][$valueId] = $offset[$value];
                        }
                    } else {
                        $response['row'][$headerId] = $offset[$header];
                    }
                }
                $response = $service->run($response, $this);
            }
        /** export */
        } elseif(in_array($response['status'], ['export', 'excel'])) {
            $service = $this->grid->getExport();
            $path = $service->getFolder() . '/' . $response['_file'];
            $response['limit'] = $service->speed($this->config['speed']);
            $response['row'] = $this->grid->prepare()->getOffsets();
            $response['sort'] = $this->grid->getPost('sort');
            $response = $service->run($response, $this);
            if('export' == $response['status']) {
                $handle = fopen('nette.safe://' . $path, 'a');
            } else {
                $excel = PHPExcel_IOFactory::load($path);
                $excel->setActiveSheetIndex(0);
                $last = $excel->getActiveSheet()->getHighestRow();
            }
            foreach($response['row'] as $rowId => $cells) {
                foreach($cells as $cellId => $cell) {
                    if($cell instanceof DateTime) {
                        $response['row'][$rowId][$cellId] = $cell->__toString();
                    } else if(false == $this->grid->getAnnotation($cellId, ['unrender', 'hidden', 'unexport']) && isset($cell['Attributes']) && isset($cell['Attributes']['value'])) {
                        $response['row'][$rowId][$cellId] = $cell['Attributes']['value'];
                    } else if(false == $this->grid->getAnnotation($cellId, ['unrender', 'hidden', 'unexport']) && !isset($cell['Attributes'])) {
                        $response['row'][$rowId][$cellId] = $cell;
                    } else {
                        unset($response['row'][$rowId][$cellId]);
                    }
                }
                if('export' == $response['status']) {
                    fputs($handle, PHP_EOL . implode(';', $response['row'][$rowId]));
                } else {
                    $last++;
                    $letter = 'a';
                    foreach ($response['row'][$rowId] as $cell) {
                        $excel->getActiveSheet()->SetCellValue($letter++ . $last, $cell);
                    }
                }
            }
            if('export' == $response['status']) {
                fclose($handle);
            } else {
                $writer = new PHPExcel_Writer_Excel2007($excel);
                $writer->save($path);
            }
            $response['offset'] = $response['offset'] + $service->speed($this->config['speed']);
        /** process */
        } else {
            $service = $this->grid->getService();
            if(!empty($response['row'] = $this->grid->prepare()->getOffsets())) {
                $response = $service->run($response, $this);
            }
            $response['offset'] = $response['offset'] + $service->speed($this->config['speed']);
        }
        $setting = $service->getSetting();
        $callbacks = is_object($setting) ? json_decode($setting->callback) : [];
        foreach ($callbacks as $callbackId => $callback) {
            $sanitize = preg_replace('/print|echo|exec|call|eval|mysql/', '', $callback);
            eval('function call($response["row"]) {' . $sanitize . '}');
            $response['row'] = call($response['row']);
        }
        $this->presenter->sendResponse(new JsonResponse($response));
    }

    public function handleSave(): void {
        $response = $this->row->getPost('');
        if($this->grid->isImport())  {
            $response = $this->grid->getImport()->save($response);
        } else {
            $response = $this->row->getImport()->save($response);
        }
        $this->presenter->sendResponse(new JsonResponse($response));
    }

    public function handleSubmit(): void {
        $this->presenter->sendResponse(new JsonResponse($this->row->submit(true)));
    }

    public function handleValidate(): void {
        $this->presenter->sendResponse(new JsonResponse($this->row->validate()));
    }

    public function render(): void {
        $this->template->assets = $this->config['assets'];
        $this->template->npm = $this->config['npm'];
        $this->template->locale = preg_replace('/(\_.*)/', '', $this->translatorRepository->getLocalization());
        $this->template->dialogs = ['help' => 1102.0, 'import' => 1101.0, 'message' => 1174.0];
        $this->template->grid = $this->grid;
        $this->template->help = $this->helpRepository->getHelp($this->presenter->getName(), $this->presenter->getAction(), $this->request->getUrl()->getQuery());
        $columns = $this->grid->getColumns();
        $this->template->order = reset($columns);
        $this->template->setFile(__DIR__ . '/templates/@layout.latte');
        $this->template->setTranslator($this->translatorRepository);
        $this->template->settings = $this->presenter->getUser()->getIdentity()->__get('settings');
        $this->template->render();
    }

    public function renderRow(): void {
        $this->template->dialogs = ['help' => 1102.0, 'import' => 1101.0, ];
        $this->template->npm = $this->config['npm'];
        $this->template->grid = $this->row;
        $this->template->help = $this->helpRepository->getHelp($this->presenter->getName(), $this->presenter->getAction(), $this->request->getUrl()->getQuery());
        $this->template->setFile(__DIR__ . '/templates/row.latte');
        $this->template->setTranslator($this->translatorRepository);
        $this->template->render();
    }

    private function sanitize(string $row, string $divider): array {
        return explode($divider, preg_replace('/\<\?php|\"/', '', $row));
    }

    public function setConfig(string $key, $value): IImpalaFactory {
        $this->config[$key] = $value;
        return $this;
    }

    public function setGrid(IBuilder $grid): IImpalaFactory {
        $this->grid = $grid;
        return $this;
    }

    public function setRow(IBuilder $row): IImpalaFactory {
        $this->row = $row;
        return $this;
    }

}

interface IImpalaFactory {

    public function create(): Impala;

}
