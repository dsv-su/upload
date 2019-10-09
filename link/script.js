function select_file(event) {
    var fileinput = document.getElementById("uploadfile")
    fileinput.click()
}

function show_file(event) {
    var filefield = document.getElementById("filename")
    filefield.value = event.currentTarget.files[0].name
}
