    </div>
    <!-- jQuery (required for DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

    <script>
        $(document).ready(function() {
            $('.table-datatable').DataTable({
                "order": [
                    [0, "desc"]
                ], // Order by first column (ID) descending
                "pageLength": 10, // Show only 1 record per page by default
                "lengthMenu": [
                    [10, 25, 50, -1],
                    [10, 25, 50, "All"]
                ],
                "searching": true, // Enable search box
                "paging": true, // Enable pagination
                "info": true, // Show "Showing 1 to X of Y entries"
                "responsive": true, // Mobile responsive
                "autoWidth": false, // Prevent auto column width issues
                "columnDefs": [{
                        "orderable": false,
                        "targets": -1
                    } // Disable sorting on last column (Actions)
                ]
            });
        });


        // page loadding 
        function waitForLoaderAndHide() {
            const spinner = document.getElementById('LoadSpinnerAll');
            if (spinner) {
                spinner.style.setProperty('display', 'none', 'important');
                document.body.style.cursor = '';
            } else {
                waitForLoaderAndHide();
            }
        }

        setTimeout(() => {
            document.getElementById('LoadSpinnerAll')?.style.setProperty('display', 'flex', 'important');
            document.body.style.cursor = 'not-allowed';
            waitForLoaderAndHide();
        }, 500);
        // page loadding 
    </script>

    </body>

    </html>