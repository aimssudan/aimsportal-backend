<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Upload Projects </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  </head>
  <body>
    <div class="col-md-5 mx-auto mt-4">
      <center><h4>Upload Project File</h4></center>
      <form action="{{ route('upload.projects') }}" method="post" enctype="multipart/form-data">
        <div class="form-group">
          <label for="file">Upload File</label>
          <input type="file" name="file_csv" class="form-control" id="file_csv">
        </div>
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <center><button type="submit" class="btn btn-primary btn-sm">Upload</button></center>
      </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  </body>
</html>