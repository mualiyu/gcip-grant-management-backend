<table>
    <thead>
    <tr>
        <th colspan="4"> REA - Applicants with application undergoing review</th>
    </tr>
    <tr>
        <th>Name</th>
        <th>Email</th>
    </tr>
    </thead>
    <tbody>
    @foreach($applications as $a)
        <tr>
            <td>{{ $a->applicant->name }}</td>
            <td>{{ $a->applicant->email }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
