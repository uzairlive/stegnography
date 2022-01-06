<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
    <section>
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                <div class="head">
                    <h3>Decrypt Your Image</h3>
                </div>
                    <form action="api/imgDecrypt" enctype="multipart/form-data" method="POST">
                        {{ csrf_field() }}
                        <div class="form-group">
                            <input type="file" name="data" id="" class="form-control">
                        </div>
                        <div class="form-group">
                            <input type="text" name="password" id="" class="form-control">
                        </div>
                        <div class="form-group">
                            <input type="submit" value="Submit" class="form-control">
                        </div>
                </div>
            </div>
        </div>
    </section>
    <section>
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                <div class="head">
                    <h3>Decrypt Your Video</h3>
                </div>
                    <form action="api/vidDecrypt" enctype="multipart/form-data" method="POST">
                        {{ csrf_field() }}
                        <div class="form-group">
                            <input type="file" name="data" id="" class="form-control">
                        </div>
                        <div class="form-group">
                            <input type="text" name="password" id="" class="form-control">
                        </div>
                        <div class="form-group">
                            <input type="submit" value="Submit" class="form-control">
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>


<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
</body>
</html>