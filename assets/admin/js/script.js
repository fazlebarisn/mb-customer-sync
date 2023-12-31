
// Create urlParams query string
var urlParams = new URLSearchParams(window.location.search);

// Get value of single parameter
var sectionName = urlParams.get('page_no');

if(sectionName){

    sectionName = parseInt(sectionName) + 1;
    // sectionName = parseInt(sectionName);
    console.log(sectionName);
    var url = 'profile.php?page=mb-customer-sync&page_no=' + sectionName;
    setTimeout(() => {
        window.location.replace(url);
    }, 2000);

}

