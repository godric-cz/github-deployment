<?php

namespace Godric\GithubDeployment;

use \Exception;

class GithubDeployment {

    private
        $repo,
        $target,
        $secret;

    const
        DEFAULT_PERMISSIONS = 0777,
        RECURSIVE = true;

    function __construct($params) {
        $this->target = $params['target'];
        $this->secret = $params['secret'];
        if(!is_dir($this->target)) throw new Exception('target directory does not exist');
        if(!is_writable($this->target)) throw new Exception('target directory not writable');
        // TODO easy support for branches -  "ref": "refs/heads/otherbranch" for other branch
    }

    /**
     *
     */
    private function apply($changes) {
        $tmpdir = $this->target . '/_github_deployment';
        mkdir($tmpdir);

        $toReplace = $changes->toReplace;
        foreach($toReplace as $i => $file) {
            $toReplace[$i] = ['name' => $file, 'tmpname' => md5($file)];
        }

        foreach($toReplace as $file) {
            $this->download($file['name'], $tmpdir . '/' . $file['tmpname']);
        }

        // replace or add new files
        foreach($toReplace as $file) {
            // make directory for file if needed (ie. new file in not yet existent directory)
            @mkdir($this->target . '/' . dirname($file['name']), self::DEFAULT_PERMISSIONS, self::RECURSIVE);
            // overwrite original file with new one
            rename($tmpdir . '/' . $file['tmpname'], $this->target . '/' . $file['name']);
        }

        // remove old files
        foreach($changes->toRemove as $file) {
            unlink($this->target . '/' . $file);
        }

        rmdir($tmpdir);
    }

    /**
     * Run deployment – read inputs from superglobal variables.
     */
    function autorun() {
        $this->run(
            $_POST['payload'],
            $_SERVER['HTTP_X_HUB_SIGNATURE'],
            file_get_contents('php://input')
        );
    }

    /**
     * Download file from github repo to target location.
     * @param string $file filename in repo like 'src/somefile.php'
     * @param string $to full target name like '/tmp/somefile.php'
     */
    private function download($file, $to) {
        $repo = $this->repo;
        $branch = 'master';
        $efile = rawurlencode($file);
        copy("https://raw.githubusercontent.com/$repo/$branch/$efile", $to);
    }

    /**
     *
     */
    private function readCommits($commits) {
        $toReplace = [];
        $toRemove  = [];

        foreach($commits as $commit) {
            foreach($commit->removed as $removed) {
                $toRemove[$removed] = true; // this is for correct multiple remove/add of single file
                $toReplace[$removed] = false;
            }
            foreach(array_merge($commit->added, $commit->modified) as $changed) {
                $toRemove[$changed] = false;
                $toReplace[$changed] = true;
            }
        }

        $diff = new \stdClass;
        $diff->toReplace = array_keys(array_filter($toReplace));
        $diff->toRemove  = array_keys(array_filter($toRemove));
        return $diff;
    }

    /**
     * Decode data and check contents.
     */
    private function readData($payload) {
        $data = json_decode($payload);
        if(!$data) throw new Exception('failed to decode payload');
        if($data->ref !== 'refs/heads/master') throw new Exception('not master branch');
        return $data;
    }

    /**
     * Run deployment.
     * @param string $payload contents of payload post field
     * @param string $signatureHeader value of http header X-Hub-Signature
     * @param string $rawInput raw contents of http request body (this string
     *  is being signed by github)
     */
    function run($payload, $signatureHeader, $rawInput) {
        $this->verify($rawInput, $signatureHeader);
        $data = $this->readData($payload);
        $this->repo = $data->repository->full_name;
        $changes = $this->readCommits($data->commits);
        $this->apply($changes);
    }

    /**
     * Verify data integrity.
     */
    private function verify($data, $signatureHeader) {
        $found = preg_match('@^sha1=([a-f0-9]+)$@', $signatureHeader, $matches);
        if(!$found) throw new Exception('failed to read signature');

        $expectedHash = $matches[1];
        $realHash     = hash_hmac('sha1', $data, $this->secret);
        if($expectedHash !== $realHash) throw new Exception('signature verification failed');
    }

}
