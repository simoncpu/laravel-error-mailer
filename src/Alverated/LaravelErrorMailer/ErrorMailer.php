<?php

namespace Alverated\LaravelErrorMailer;

class ErrorMailer
{
    protected $base_path;
    protected $e;

    public function __construct(\Exception $e)
    {
        $this->e = $e;
        $this->base_path = base_path();
    }

    public function sendError()
    {
        $config = config(env('APP_ENV') . '.settings');
        $output = '';
        $file = $this->e->getFile();
        $line = $this->e->getLine();
        $trace = $this->e->getTrace();

        if (file_exists($file)) {
            try {
                $f = file($file);
                $line = (int)$line;

                for ($i = $line - 5; $i <= $line + 5; $i++) {
                    if (isset($f[$i])) {
                        $output .= ($i + 1) . ": " . $f[$i];
                    }
                }
            } catch (\Exception $e) {

            }
        } else {
            if (is_array($trace) && !empty($trace)) {
                $elem = current($trace);

                if (is_array($elem)) {
                    if (isset($elem['file']) && isset($elem['line'])) {
                        try {
                            $file = file($elem['file']);
                            $line = (int)$elem['line'];

                            for ($i = $line - 5; $i <= $line + 5; $i++) {
                                if (isset($file[$i])) {
                                    $output .= ($i + 1) . ": " . $file[$i];
                                }
                            }
                        } catch (\Exception $e) {
                            \Log::error('Email:Error:Reporting: ' . $e->getMessage());
                        }
                    }
                }
            }
        }

        try {

            $request = array();
            $request['fullUrl'] = request()->fullUrl();
            $request['input_get'] = isset($_GET) ? $_GET : null;
            $request['input_post'] = isset($_POST) ? $_POST : null;
            $request['session'] = session()->all();
            $request['cookie'] = request()->cookie();
            $request['file'] = request()->file();
            $request['header'] = request()->header();
            $request['server'] = request()->server();
            $request['output'] = $output;
            $request['json'] = request()->json();
            $request['request_format'] = request()->format();
            $request['error'] = $this->e->getTraceAsString();
            $request['subject_line'] = $this->e->getMessage();
            $request['error_line'] = $line;
            $request['error_file'] = $file;
            $request['class_name'] = get_class($this->e);
            $request['reported_by'] = isset($config['site_name']) ? 'Team ' . $config['site_name'] : 'Error Handler';

            $data = [
                'tempData' => $request,
            ];

            $this->exec($data);

        } catch (Exception $e) {
            \Log::error('Email:Error:Reporting: ' . $e->getMessage());
        }
    }

    public function exec($data)
    {
        $exec = 'php -f ' . dirname(__FILE__) . '/Mailer.php' . ' ' . urlencode(json_encode($data)) . ' > /dev/null 2>/dev/null &';
        `$exec`;
    }
}