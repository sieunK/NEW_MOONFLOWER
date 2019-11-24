<?php

StartAnalysis("201924506", CodeType::C);

class CodeType {
    const C = 1;
    const Java = 2;
    const Python = 3;
}

function StartAnalysis($projectKey, $codeType)
{
    if($codeType == CodeType::C)
        CheckC($projectKey);
    if($codeType == CodeType::Java)
        CheckJava($projectKey);
    if($codeType == CodeType::Python)
        CheckPython($projectKey);
}

function AnalysisFinished()
{
    //
}

function GetDataWithFile($path)
{
    $fp = fopen($path, "r");
    $data = "";

    while(!feof($fp))
    {
        $t = fgets($fp);

        if(substr($t, 0, 4) === "Code")
            $data .= $t;
    }
    fclose($fp);

    unlink($path);

    return $data;
}

//Use CppCheck
function CheckC()
{
    exec ("cppcheck --enable=all --output-file=temp Code");

    $fw = fopen("result.txt", "w");
    fwrite ($fw, GetDataWithFile("temp"));
    fclose($fw);

    AnalysisFinished();
}

function CheckJava($projectKey)
{
    SetSornaQube($projectKey);

    $codeFile = 'Code';
    $destinationFile = './src/main/java/Main.java';

    if(file_exists($codeFile)) {
        if(!copy($codeFile, $destinationFile))
            echo "파일 복사 실패";
        else if(file_exists($codeFile))
            echo "파일 복사 성공";
    }

    exec("mvn sonar:sonar -Dsonar.projectKey={$projectKey} -Dsonar.host.url=http://localhost:9000 -Dsonar.login=1116f0b5f80bc8c0a8a74d33068fefeae8ad9421");

    GetIssues($projectKey);
}

function CheckPython($projectKey)
{
    SetSornaQube($projectKey);

    $codeFile = 'Code';
    $destinationFile = './PythonCodes/Code.py';

    if(file_exists($codeFile)) {
        if(!copy($codeFile, $destinationFile))
            echo "파일 복사 실패";
        else if(file_exists($codeFile))
            echo "파일 복사 성공";
    }

    exec("sonar-scanner -Dsonar.projectKey={$projectKey} -Dsonar.sources=./PythonCodes -Dsonar.host.url=http://localhost:9000 -Dsonar.login=1116f0b5f80bc8c0a8a74d33068fefeae8ad9421");

    GetIssues($projectKey);
}

function SetSornaQube($projectKey)
{
    //Make Project
    exec("curl -u admin:admin -X POST 'http://localhost:9000/api/projects/create?project={$projectKey}&name={$projectKey}'");
}

function GetDataWithJson($jsonData)
{
    $ignoreMessags = array(
        "Replace this use of System.out or System.err by a logger.",
        "Move this file to a named package."
    );

    $data = json_decode($jsonData, true);
    $issues = $data['issues'];

    $result = "";
    foreach ($issues as $issue)
        if(!in_array($issue['message'], $ignoreMessags))
            $result .= "Line : ".$issue['textRange']['startLine']." ".$issue['message']."\n";

    return $result;
}

function GetIssues($projectKey)
{
    //Wait Upload
    do {
        sleep(3);

        exec ("curl -u admin:admin http://localhost:9000/api/qualitygates/project_status?projectKey={$projectKey}", $currentState);

        $state = json_decode($currentState[0], true);
    } while($state['projectStatus']['status'] == 'NONE');
    //Get Issues
    exec ("curl -u admin:admin http://localhost:9000/api/issues/search?projectKeys={$projectKey}&severities=CRITICAL,BLOCKER,MAJOR,MINOR", $output);

    $fw = fopen("result.txt", "w");
    fwrite ($fw, GetDataWithJson($output[0]));
    fclose($fw);

    //Delete Proejct
    exec ("curl -u admin:admin -X POST http://localhost:9000/api/projects/delete?project={$projectKey}");

    AnalysisFinished();
}
?>
